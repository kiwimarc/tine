<?php declare(strict_types=1);
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

class Tinebase_Auth_MFATest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Tinebase_Auth_MFA::destroyInstances();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        Tinebase_Auth_MFA::destroyInstances();
        Tinebase_AreaLock::destroyInstance();
    }

    public function testYubicoOTP()
    {
        if (empty(Tinebase_Config::getInstance()->{Tinebase_Auth_CredentialCache_Adapter_Shared::CONFIG_KEY})) {
            self::markTestSkipped('shared credential cache key CONFIG_KEY required for this test');
        }

        $this->_originalTestUser->mfa_configs = new Tinebase_Record_RecordSet(
            Tinebase_Model_MFA_UserConfig::class, [[
            Tinebase_Model_MFA_UserConfig::FLD_ID => 'yubicoOTPunittest',
            Tinebase_Model_MFA_UserConfig::FLD_MFA_CONFIG_ID => 'unittest',
            Tinebase_Model_MFA_UserConfig::FLD_CONFIG_CLASS =>
                Tinebase_Model_MFA_YubicoOTPUserConfig::class,
            Tinebase_Model_MFA_UserConfig::FLD_CONFIG =>
                new Tinebase_Model_MFA_YubicoOTPUserConfig([
                    Tinebase_Model_MFA_YubicoOTPUserConfig::FLD_PUBLIC_ID => 'vvccccdhdtnh',
                    Tinebase_Model_MFA_YubicoOTPUserConfig::FLD_PRIVAT_ID => '1449e1c9cd4c',
                    Tinebase_Model_MFA_YubicoOTPUserConfig::FLD_AES_KEY => '9a9798f480da0193ab7be4e8abc952c2',
                ]),
        ]]);

        $this->_createAreaLockConfig([], [
            Tinebase_Model_MFA_Config::FLD_ID => 'unittest',
            Tinebase_Model_MFA_Config::FLD_USER_CONFIG_CLASS =>
                Tinebase_Model_MFA_YubicoOTPUserConfig::class,
            Tinebase_Model_MFA_Config::FLD_PROVIDER_CONFIG_CLASS =>
                Tinebase_Model_MFA_YubicoOTPConfig::class,
            Tinebase_Model_MFA_Config::FLD_PROVIDER_CLASS =>
                Tinebase_Auth_MFA_YubicoOTPAdapter::class,
            Tinebase_Model_MFA_Config::FLD_PROVIDER_CONFIG => []
        ]);

        $this->_originalTestUser = Tinebase_User::getInstance()->updateUser($this->_originalTestUser);
        $mfa = Tinebase_Auth_MFA::getInstance('unittest');

        $this->assertFalse($mfa->validate('shaaaaaaaaaalala', $this->_originalTestUser->mfa_configs->getFirstRecord()),
            'validate didn\'t fail as expected');
        $this->assertTrue($mfa->validate('vvccccdhdtnhleteeguflgbchbgfcbvbclnkknethrfv', $this->_originalTestUser
            ->mfa_configs->getFirstRecord()), 'validate didn\'t succeed');
        $this->_originalTestUser = Tinebase_User::getInstance()->getUserById($this->_originalTestUser->getId(),
            Tinebase_Model_FullUser::class);
        $this->assertFalse($mfa->validate('vvccccdhdtnhleteeguflgbchbgfcbvbclnkknethrfv', $this->_originalTestUser
            ->mfa_configs->getFirstRecord()), 'validate didn\'t fail as expected on second call');

        $this->assertTrue($mfa->validate('vvccccdhdtnhtrbtrhtbvfldecgjevlutenjkgugglfh', $this->_originalTestUser
            ->mfa_configs->getFirstRecord()), 'validate didn\'t succeed');
        $this->_originalTestUser = Tinebase_User::getInstance()->getUserById($this->_originalTestUser->getId(),
            Tinebase_Model_FullUser::class);
        $this->assertFalse($mfa->validate('vvccccdhdtnhleteeguflgbchbgfcbvbclnkknethrfv', $this->_originalTestUser
            ->mfa_configs->getFirstRecord()), 'validate didn\'t fail as expected on second call');
        $this->assertFalse($mfa->validate('vvccccdhdtnhtrbtrhtbvfldecgjevlutenjkgugglfh', $this->_originalTestUser
            ->mfa_configs->getFirstRecord()), 'validate didn\'t succeed');
    }

    public function testGenericSmsAdapter()
    {
        $this->_originalTestUser->mfa_configs = new Tinebase_Record_RecordSet(
            Tinebase_Model_MFA_UserConfig::class, [[
            Tinebase_Model_MFA_UserConfig::FLD_ID => 'userunittest',
                Tinebase_Model_MFA_UserConfig::FLD_MFA_CONFIG_ID => 'unittest',
                Tinebase_Model_MFA_UserConfig::FLD_CONFIG_CLASS =>
                    Tinebase_Model_MFA_SmsUserConfig::class,
                Tinebase_Model_MFA_UserConfig::FLD_CONFIG =>
                    new Tinebase_Model_MFA_SmsUserConfig([
                        Tinebase_Model_MFA_SmsUserConfig::FLD_CELLPHONENUMBER => '1234567890',
                    ]),
            ]]);

        $this->_createAreaLockConfig([], [
            Tinebase_Model_MFA_Config::FLD_ID => 'unittest',
            Tinebase_Model_MFA_Config::FLD_USER_CONFIG_CLASS =>
                Tinebase_Model_MFA_SmsUserConfig::class,
            Tinebase_Model_MFA_Config::FLD_PROVIDER_CONFIG_CLASS =>
                Tinebase_Model_MFA_GenericSmsConfig::class,
            Tinebase_Model_MFA_Config::FLD_PROVIDER_CLASS =>
                Tinebase_Auth_MFA_GenericSmsAdapter::class,
            Tinebase_Model_MFA_Config::FLD_PROVIDER_CONFIG => [
                Tinebase_Model_MFA_GenericSmsConfig::FLD_URL => 'https://shoo.tld/restapi/message',
                Tinebase_Model_MFA_GenericSmsConfig::FLD_BODY => '{"encoding":"auto","body":"{{ message }}","originator":"{{ app.branding.title }}","recipients":["{{ cellphonenumber }}"],"route":"2345"}',
                Tinebase_Model_MFA_GenericSmsConfig::FLD_METHOD => 'POST',
                Tinebase_Model_MFA_GenericSmsConfig::FLD_HEADERS => [
                    'Auth-Bearer' => 'unittesttokenshaaaaalalala'
                ],
                Tinebase_Model_MFA_GenericSmsConfig::FLD_PIN_TTL => 600,
                Tinebase_Model_MFA_GenericSmsConfig::FLD_PIN_LENGTH => 6,
            ]
        ]);

        $mfa = Tinebase_Auth_MFA::getInstance('unittest');
        $mfa->getAdapter()->setHttpClientConfig([
            'adapter' => ($httpClientTestAdapter = new Tinebase_ZendHttpClientAdapter())
        ]);
        $httpClientTestAdapter->setResponse(new Zend_Http_Response(200, []));

        $this->assertTrue($mfa->sendOut($this->_originalTestUser->mfa_configs->getFirstRecord()),
            'sendOut didn\'t succeed');
        $sessionData = Tinebase_Session::getSessionNamespace()->{Tinebase_Auth_MFA_GenericSmsAdapter::class};
        $this->assertIsArray($sessionData, 'session data not set properly');
        $this->assertArrayHasKey('ttl', $sessionData, 'session data not set properly');
        $this->assertArrayHasKey('pin', $sessionData, 'session data not set properly');
        $this->assertStringContainsString('"body":"' . $sessionData['pin'] . ' is your ',
            $httpClientTestAdapter->lastRequestBody);
        $this->assertStringContainsString('"recipients":["+491234567890"],"route":"2345"',
            $httpClientTestAdapter->lastRequestBody);

        $this->assertFalse($mfa->validate('shaaaaaaaaaalala', $this->_originalTestUser->mfa_configs->getFirstRecord()),
            'validate didn\'t fail as expected');
        $this->assertTrue($mfa->validate($sessionData['pin'], $this->_originalTestUser->mfa_configs->getFirstRecord()),
            'validate didn\'t succeed');
        $this->assertFalse($mfa->validate($sessionData['pin'], $this->_originalTestUser->mfa_configs->getFirstRecord()),
            'validate didn\'t fail as expected on second call');
    }
}
