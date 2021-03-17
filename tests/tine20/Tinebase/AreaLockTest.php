<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2018-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 * @todo add more providers (in separate test classes?)
 */

/**
 * Test class for Tinebase_AreaLock
 */
class Tinebase_AreaLockTest extends TestCase
{
    /**
     * unit in test
     *
     * @var Tinebase_AreaLock
     */
    protected $_uit = null;

    /**
     * set up tests
     */
    protected function setUp(): void
    {
        parent::setUp();
        Tinebase_AreaLock::destroyInstance();
        $this->_uit = Tinebase_AreaLock::getInstance();
        $this->_uit->activatedByFE();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Tinebase_AreaLock::destroyInstance();
    }

    public function testGetState()
    {
        $this->_createAreaLockConfig();
        $states = $this->_uit->getState(Tinebase_Model_AreaLockConfig::AREA_LOGIN);
        $this->assertTrue($states instanceof Tinebase_Record_RecordSet);
        $this->assertSame(1, $states->count());
        $state = $states->getFirstRecord();
        self::assertEquals(new Tinebase_DateTime('1970-01-01'), $state->expires);
        self::assertEquals('login', $state->area);

        $this->_setPin();
        $this->_uit->unlock('login', 'userpin', $this->_pin, Tinebase_Core::getUser());
        $state = $this->_uit->getState(Tinebase_Model_AreaLockConfig::AREA_LOGIN)->getFirstRecord();
        self::assertEquals(new Tinebase_DateTime('2150-01-01'), $state->expires);
    }

    /**
     * test unlock with correct and incorrect pins
     */
    public function testUnlock()
    {
        $this->_createAreaLockConfig();
        $this->_setPin();

        $incorrectPin = '5678';
        try {
            $state = $this->_uit->unlock('login', 'userpin', $incorrectPin, Tinebase_Core::getUser());
            self::fail('wrong pin should throw exception - ' . print_r($state->toArray(), true));
        } catch (Exception $e) {
            self::assertTrue($e instanceof Tinebase_Exception_AreaUnlockFailed);
            self::assertEquals('login', $e->getArea());
        }

        $state = $this->_uit->unlock('login', 'userpin', $this->_pin, Tinebase_Core::getUser());
        // @ŧodo expect session lifetime as expiry date?
        self::assertEquals(new Tinebase_DateTime('2150-01-01'), $state->expires, 'area should be unlocked');
    }

    public function testLock()
    {
        $this->_createAreaLockConfig();
        $this->_setPin();
        $state = $this->_uit->unlock('login', 'userpin', $this->_pin, Tinebase_Core::getUser());
        self::assertEquals(new Tinebase_DateTime('2150-01-01'), $state->expires);

        $state = $this->_uit->lock('login');
        self::assertEquals(new Tinebase_DateTime('1970-01-01'), $state->expires);
    }

    public function isLocked()
    {
        $this->_createAreaLockConfig();
        $isLocked = $this->_uit->unlock('login', 'userpin', $this->_pin, Tinebase_Core::getUser());
        self::assertTrue($isLocked);

        $this->testUnlock();
        $isLocked = $this->_uit->unlock('login', 'userpin', $this->_pin, Tinebase_Core::getUser());
        self::assertFalse($isLocked);
    }

    /**
     * @param string $validity
     * @see 0013328: protect applications with second factor
     */
    public function testAppProtection($validity = Tinebase_Model_AreaLockConfig::VALIDITY_SESSION)
    {
        Tasks_Controller_Task::getInstance()->resetValidatedAreaLock();

        $this->_createAreaLockConfig([
            Tinebase_Model_AreaLockConfig::FLD_AREAS => ['Tasks'],
            Tinebase_Model_AreaLockConfig::FLD_VALIDITY => $validity,
        ]);
        $this->_setPin();

        // try to access app
        try {
            Tasks_Controller_Task::getInstance()->getAll();
            self::fail('it should not be possible to access app without PIN');
        } catch (Tinebase_Exception $te) {
            self::assertTrue($te instanceof Tinebase_Exception_AreaLocked);
            self::assertStringContainsString('Tasks.Tasks_Model_Task.get', $te->getMessage());
            self::assertEquals('login', $te->getArea());
            $exception = $te->toArray();
            $this->assertArrayHasKey('mfaUserConfigs', $exception);
            $this->assertCount(1, $exception['mfaUserConfigs']);
        }

        $this->_uit->unlock('login', 'userpin', $this->_pin, Tinebase_Core::getUser());

        // try to access app again
        $result = Tasks_Controller_Task::getInstance()->getAll();
        self::assertGreaterThanOrEqual(0, count($result));
    }

    /**
     * testAppProtectionWithPresence
     */
    public function testAppProtectionWithPresence()
    {
        $this->testAppProtection(Tinebase_Model_AreaLockConfig::VALIDITY_PRESENCE);
    }

    /**
     * create VALIDITY_PRESENCE config and test report presence
     */
    public function testLockWithPresence()
    {
        $this->_createAreaLockConfig([
            'lifetime' => 5 / 60,
            'validity' => Tinebase_Model_AreaLockConfig::VALIDITY_PRESENCE,
        ]);
        $this->_setPin();
        $this->_uit->unlock('login', 'userpin', $this->_pin, Tinebase_Core::getUser());
        sleep(3);
        self::assertFalse($this->_uit->isLocked(Tinebase_Model_AreaLockConfig::AREA_LOGIN), 'should be unlocked for at least 5 secs');
        Tinebase_Presence::getInstance()->reportPresence();
        sleep(3);
        self::assertFalse($this->_uit->isLocked(Tinebase_Model_AreaLockConfig::AREA_LOGIN), 'should still be unlocked - presence was reported');
        sleep(3);
        self::assertTrue($this->_uit->isLocked(Tinebase_Model_AreaLockConfig::AREA_LOGIN), 'should now be locked - no presence was reported for 6 secs');
    }

    /**
     * create VALIDITY_SESSION config with lifetime of 5 secs
     */
    public function testLockWithLifetime()
    {
        $this->_createAreaLockConfig([
            'lifetime' => 5 / 60,
            'validity' => Tinebase_Model_AreaLockConfig::VALIDITY_LIFETIME,
        ]);
        $this->_setPin();
        $this->_uit->unlock('login', 'userpin', $this->_pin, Tinebase_Core::getUser());
        self::assertFalse($this->_uit->isLocked(Tinebase_Model_AreaLockConfig::AREA_LOGIN));
        sleep(6);

        self::assertTrue($this->_uit->isLocked(Tinebase_Model_AreaLockConfig::AREA_LOGIN),
            'should be locked again after 6 seconds');
    }

    /**
     * called in \Tinebase_Frontend_Json::_getUserRegistryData
     */
    public function testGetAllStates()
    {
        $this->_createAreaLockConfig();
        $states = $this->_uit->getAllStates();
        self::assertEquals(1, count($states));
        $state = $states->getFirstRecord();
        self::assertEquals('login', $state->area);
        self::assertEquals(new Tinebase_DateTime('1970-01-01'), $state->expires);
    }

    /**
     * test PROVIDER_USERPASSWORD
     */
    public function testUserPasswordProvider()
    {
        static::markTestSkipped('password provider currently not implemented');

        $this->_createAreaLockConfig([
            'provider' => Tinebase_Model_AreaLockConfig::PROVIDER_USERPASSWORD
        ]);
        $credentials = TestServer::getInstance()->getTestCredentials();
        $this->_uit->unlock('login', 'userpin', $credentials['password'], Tinebase_Core::getUser());
        self::assertFalse($this->_uit->isLocked(Tinebase_Model_AreaLockConfig::AREA_LOGIN));
    }

    /**
     * test PROVIDER_TOKEN with Tinebase_Auth_Mock
     */
    public function testUserMockTokenProvider()
    {
        static::markTestSkipped('\Tinebase_Model_AreaLockConfig::toArray removes the provider_config, like that we cant get it into the config!');
        
        $this->_createAreaLockConfig([
            'provider' => Tinebase_Model_AreaLockConfig::PROVIDER_TOKEN,
            'provider_config' => [
                'adapter' => 'Mock', // \Tinebase_Auth_Mock
                'url' => 'https://localhost/validate/check',
            ]
        ]);
        $this->_uit->unlock('login', 'phil', 'phil');
        self::assertFalse($this->_uit->isLocked(Tinebase_Model_AreaLockConfig::AREA_LOGIN));
    }
}
