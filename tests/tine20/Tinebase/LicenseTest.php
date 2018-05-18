<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2014-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test class for Tinebase_License
 * 
 * @package     Tinebase
 */
class Tinebase_LicenseTest extends TestCase
{
    /**
     * tear down tests
     */
    protected function tearDown()
    {
        parent::tearDown();
        
        // delete license files
        Tinebase_FileSystem::getInstance()->clearStatCache();
        Tinebase_FileSystem::getInstance()->clearDeletedFilesFromFilesystem();
    }
    
    public function testIsValidWithValidLicense()
    {
        $license = new Tinebase_License(dirname(__FILE__) . '/License/V-12345.pem');
        $this->assertTrue($license->isValid());
    }

    public function testIsValidWithOutdatedLicense()
    {
        $license = new Tinebase_License(dirname(__FILE__) . '/License/V-outdated.pem');
        $this->assertFalse($license->isValid());
    }

    public function testLicenseProperties()
    {
        $license = new Tinebase_License(dirname(__FILE__) . '/License/V-12345.pem');
        $certData = $license->getCertificateData();
        
        $this->assertEquals(5, $certData['policies'][101][1], '5 users limit expected');
        $this->assertEquals(5, $license->getMaxUsers(), '5 users limit expected');
        $this->assertEquals('2015-11-07 12:54:20', $certData['validTo']->toString());
        $this->assertEquals('V-12345', $certData['contractId'], 'contract id mismatch');
    }

    public function testLicensePropertiesV123456()
    {
        $license = new Tinebase_License(dirname(__FILE__) . '/License/V-123456.pem');
        $certData = $license->getCertificateData();

        $this->assertEquals(5, $certData['policies'][101][1], '5 users limit expected');
        $this->assertEquals(5, $license->getMaxUsers(), '5 users limit expected');
        $this->assertEquals('2025-03-02 16:45:09', $certData['validTo']->toString());
        $this->assertEquals('V-123456', $certData['contractId'], 'contract id mismatch');
        $this->assertEquals(2, count($certData['policies'][101]), 'not all policies were found: ' . print_r($certData['policies'], true));
        $this->assertTrue(isset($certData['policies'][103]), 'not all policies were found: ' . print_r($certData['policies'], true));
        $this->assertEquals('limitedUserTime', $certData['policies'][103][1], 'license type mismatch');
    }

    public function testLicensePropertiesVonDemand()
    {
        $license = new Tinebase_License(dirname(__FILE__) . '/License/V-onDemand.pem');
        $certData = $license->getCertificateData();

        $this->assertEquals(0, $certData['policies'][101][1], '0 users limit expected');
        $this->assertEquals(0, $license->getMaxUsers(), '0 users limit expected');
        $this->assertEquals(true, $license->checkUserLimit(), 'no user limit expected');
        $this->assertEquals('2025-03-02 16:31:28', $certData['validTo']->toString());
        $this->assertEquals('onDemand', $certData['policies'][103][1], 'license type mismatch');
    }

    public function testLicensePropertiesLimitedTime()
    {
        $license = new Tinebase_License(dirname(__FILE__) . '/License/V-limitedTime.pem');
        $this->assertEquals($license->getLicenseType(), Tinebase_License::LICENSE_TYPE_LIMITED_TIME);
    }

    public function testStoreLicense()
    {
        $license = new Tinebase_License();
        $license->storeLicense(file_get_contents(dirname(__FILE__) . '/License/V-12345.pem'));
        
        $certData = $license->getCertificateData();
        $this->assertEquals('2015-11-07 12:54:20', $certData['validTo']->toString());
    }

    public function testInitLicense()
    {
        $this->testStoreLicense();
        $license = new Tinebase_License();
        $certData = $license->getCertificateData();
        $this->assertEquals('2015-11-07 12:54:20', $certData['validTo']->toString());
    }
    
    public function testCreateUserWithLimitExceeded()
    {
        $this->testStoreLicense();
        $testUser = $this->_getUser();
        try {
            $user = Admin_Controller_User::getInstance()->create($testUser, 'test', 'test');
            $this->fail('user creation should fail');
        } catch (Exception $e) {
            $this->assertTrue($e instanceof Tinebase_Exception_SystemGeneric);
        }
    }

    protected function _getUser()
    {
        return new Tinebase_Model_FullUser(array(
            'accountLoginName' => Tinebase_Record_Abstract::generateUID(),
            'accountPrimaryGroup' => Tinebase_Group::getInstance()->getDefaultGroup()->getId(),
            'accountDisplayName' => Tinebase_Record_Abstract::generateUID(),
            'accountLastName' => Tinebase_Record_Abstract::generateUID(),
            'accountFullName' => Tinebase_Record_Abstract::generateUID(),
        ));
    }

    public function testCreateUserWithLimitExceededWithOnDemandLicense()
    {
        $license = new Tinebase_License();
        $license->storeLicense(file_get_contents(dirname(__FILE__) . '/License/V-onDemand.pem'));

        $testUser = $this->_getUser();
        $user = Admin_Controller_User::getInstance()->create($testUser, 'test', 'test');
        $this->assertTrue(is_string($user->getId()));
    }

    public function testUserLimitExceeded()
    {
        $testUser = new Tinebase_Model_FullUser(array(
            'accountLoginName' => Tinebase_Record_Abstract::generateUID(),
            'accountPrimaryGroup' => Tinebase_Group::getInstance()->getDefaultGroup()->getId(),
            'accountDisplayName' => Tinebase_Record_Abstract::generateUID(),
            'accountLastName' => Tinebase_Record_Abstract::generateUID(),
            'accountFullName' => Tinebase_Record_Abstract::generateUID(),
        ));
        $user = Admin_Controller_User::getInstance()->create($testUser, 'test', 'test');
        $this->_usernamesToDelete[] = $testUser->accountLoginName;
        $this->testStoreLicense();
        $license = new Tinebase_License();

        $this->assertFalse($license->checkUserLimit($user));
    }

    public function testLicenseStatusInRegistry()
    {
        $tfj = new Tinebase_Frontend_Json();
        $registry = $tfj->getRegistryData();
        $this->assertEquals(Tinebase_License::STATUS_NO_LICENSE_AVAILABLE, $registry['licenseStatus']);
        
        $license = new Tinebase_License();
        $license->storeLicense(file_get_contents(dirname(__FILE__) . '/License/V-outdated.pem'));
        $registry = $tfj->getRegistryData();
        $this->assertEquals(Tinebase_License::STATUS_LICENSE_INVALID, $registry['licenseStatus']);
        
        $license->storeLicense(file_get_contents(dirname(__FILE__) . '/License/V-12345.pem'));
        $registry = $tfj->getRegistryData();
        $this->assertEquals(Tinebase_License::STATUS_LICENSE_OK, $registry['licenseStatus']);

        // TODO allow to delete license
//         $license->storeLicense('');
//         $registry = $tfj->getRegistryData();
//         $this->assertEquals(Tinebase_License::STATUS_NO_LICENSE_AVAILABLE, $registry['licenseStatus']);
    }

    public function testFirstUserCreationTime()
    {
        $userCreationTime = Tinebase_Core::getUser()->creation_time;
        if (! $userCreationTime instanceOf Tinebase_DateTime) {
            $this->markTestSkipped('older installation');
        }
        
        $creationTime = Tinebase_User::getInstance()->getFirstUserCreationTime();
        $this->assertEquals($creationTime->toString(), Tinebase_Core::getUser()->creation_time->toString());
        
        return $creationTime;
    }
    
    public function testNoLicenseValidTimestamps()
    {
        $firstUserCreationTime = $this->testFirstUserCreationTime();
        $license = new Tinebase_License();
        $this->assertEquals(Tinebase_License::STATUS_NO_LICENSE_AVAILABLE, $license->getStatus());
        $data = $license->getCertificateData();
        
        $this->assertTrue($data['validFrom'] instanceof Tinebase_DateTime && $data['validTo'] instanceof Tinebase_DateTime);
        $this->assertEquals($firstUserCreationTime->toString(), $data['validFrom']->toString());
        $this->assertEquals($firstUserCreationTime->addDay(20)->toString(), $data['validTo']->toString());
    }

    public function testLicenseExpiredSince()
    {
        $license = new Tinebase_License(dirname(__FILE__) . '/License/V-outdated.pem');
        $expiredSinceDays = $license->getLicenseExpiredSince();
        
        $now = Tinebase_DateTime::now();
        $validTo = new Tinebase_DateTime('2014-11-08 12:55:54');
        $diff = $now->diff($validTo);
        
        $this->assertEquals($diff->days, $expiredSinceDays);
    }
    
    public function testLicenseExpiredEstimate()
    {
        $creationTime = $this->testFirstUserCreationTime();
        if ($creationTime->isEarlier(Tinebase_DateTime::now()->setTime(0, 0))) {
            $this->markTestSkipped('older installation');
        }

        $license = new Tinebase_License();
        $data = $license->getCertificateData();
        $now = Tinebase_DateTime::now();
        $diff = $now->diff($data['validTo']);

        $daysLeft = $license->getLicenseExpireEstimate();

        $this->assertEquals($diff->days, $daysLeft, print_r($diff, true));
    }

    public function testLicenseUploadByFrontend()
    {
        $sfj = new Setup_Frontend_Json();

        $tempfileName = 'testupload' . Tinebase_Record_Abstract::generateUID(10);
        $tempfilePath = Tinebase_Core::getTempDir() . DIRECTORY_SEPARATOR . $tempfileName;
        file_put_contents($tempfilePath, file_get_contents(dirname(__FILE__) . '/License/V-12345.pem'));

        $tempFile = Tinebase_TempFile::getInstance()->createTempFile($tempfilePath, $tempfileName, 'application/x-x509-ca-cert');

        $licenseData = $sfj->uploadLicense($tempFile->getId());

        // Clean up.
        Tinebase_TempFile::getInstance()->delete($tempFile->getId());

        $this->assertEquals($licenseData['serialNumber'], 8);
    }

    public function testGetInstallationData()
    {
        $license = new Tinebase_License(dirname(__FILE__) . '/License/V-12345.pem');
        $installationData = $license->getInstallationData();

        $this->assertArrayHasKey('bits', $installationData);
        $this->assertArrayHasKey('rsa', $installationData);
    }
}
