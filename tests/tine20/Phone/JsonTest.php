<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Phone
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test class for Phone_JsonTest
 */
class Phone_JsonTest extends TestCase
{
    /**
     * Backend
     *
     * @var Phone_Frontend_Json
     */
    protected $_json;
    
    /**
     * the admin user
     * 
     * @var Tinebase_Model_FullUser
     */
    protected $_adminUser;
    
    /**
     * @var array test objects
     */
    protected $objects = array();
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Phone Json Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        parent::setUp();
        
        $this->_json = new Phone_Frontend_Json();
        $this->_adminUser = Tinebase_Core::getUser();

        // TODO move this to a common place
        $this->_objects['location'] = new Voipmanager_Model_Snom_Location(array(
            'id'        => Tinebase_Record_Abstract::generateUID(),
            'name'      => 'phpunit test location',
            'registrar' => 'registrar'
        ));

        $this->_objects['software'] = new Voipmanager_Model_Snom_Software(array(
            'id' => Tinebase_Record_Abstract::generateUID()
        ));
        
        $this->_objects['setting'] = new Voipmanager_Model_Snom_Setting(array(
            'id'          => Tinebase_Record_Abstract::generateUID(),
            'name'        => 'test setting',
            'description' => 'test setting',
        ));

        $this->_objects['template'] = new Voipmanager_Model_Snom_Template(array(
            'id'          => Tinebase_Record_Abstract::generateUID(),
            'name'        => 'phpunit test location',
            'model'       => 'snom320',
            'software_id' => $this->_objects['software']->getId(),
            'setting_id'  => $this->_objects['setting']->getId()
        ));
        
        $this->_objects['phone'] = new Voipmanager_Model_Snom_Phone(array(
            'id'             => Tinebase_Record_Abstract::generateUID(),
            'macaddress'     => "1234567890cd",
            'description'    => 'user phone',
            'location_id'    => $this->_objects['location']->getId(),
            'template_id'    => $this->_objects['template']->getId(),
            'current_model'  => 'snom320',
            'redirect_event' => 'none'
        ));
        
        $this->_objects['phone1'] = new Voipmanager_Model_Snom_Phone(array(
            'id'             => Tinebase_Record_Abstract::generateUID(),
            'macaddress'     => "a234567890cd",
            'description'    => 'second user phone',
            'location_id'    => $this->_objects['location']->getId(),
            'template_id'    => $this->_objects['template']->getId(),
            'current_model'  => 'snom320',
            'redirect_event' => 'none'
        ));
        
        $this->_objects['phone2'] = new Voipmanager_Model_Snom_Phone(array(
            'id'             => Tinebase_Record_Abstract::generateUID(),
            'macaddress'     => "1a34567890cd",
            'description'    => 'jsmith phone',
            'location_id'    => $this->_objects['location']->getId(),
            'template_id'    => $this->_objects['template']->getId(),
            'current_model'  => 'snom320',
            'redirect_event' => 'none'
        ));
        
        $this->_objects['phonesetting'] = new Voipmanager_Model_Snom_PhoneSettings(array(
            'phone_id'     => $this->_objects['phone']->getId(),
            'web_language' => 'English'
        ));
        
        $this->_objects['phonesetting1'] = new Voipmanager_Model_Snom_PhoneSettings(array(
            'phone_id'     => $this->_objects['phone1']->getId(),
            'web_language' => 'English'
        ));
        
        $this->_objects['phonesetting2'] = new Voipmanager_Model_Snom_PhoneSettings(array(
            'phone_id'     => $this->_objects['phone2']->getId(),
            'web_language' => 'English'
        ));
        
        $this->_objects['phoneOwner'] = array(
            'account_id' => $this->_adminUser->getId(),
            'account_type' => 'user'
        );
        
        $this->_objects['phoneOwner2'] = array(
            'account_id' => $this->_personas['jsmith']->getId(),
            'account_type' => 'user'
        );
        
        $rights = new Tinebase_Record_RecordSet('Voipmanager_Model_Snom_PhoneRight', array(
            $this->_objects['phoneOwner']
        ));
        
        $rights1 = new Tinebase_Record_RecordSet('Voipmanager_Model_Snom_PhoneRight', array(
            $this->_objects['phoneOwner']
        ));
        
        $rights2 = new Tinebase_Record_RecordSet('Voipmanager_Model_Snom_PhoneRight', array(
            $this->_objects['phoneOwner2']
        ));
        
        $this->_objects['phone']->rights = $rights;
        $this->_objects['phone1']->rights = $rights1;
        $this->_objects['phone2']->rights = $rights2;
        
        $this->_objects['context'] = new Voipmanager_Model_Asterisk_Context(array(
            'id'                => Tinebase_Record_Abstract::generateUID(),
            'name'              => 'test context',
            'description'       => 'test context',
        ));
        
        $this->_objects['sippeer'] = new Voipmanager_Model_Asterisk_SipPeer(array(
            'id'                => Tinebase_Record_Abstract::generateUID(),
            'context_id'        => $this->_objects['context']->getId()
        ));
        $this->_objects['sippeer1'] = new Voipmanager_Model_Asterisk_SipPeer(array(
            'id'                => Tinebase_Record_Abstract::generateUID(),
            'context_id'        => $this->_objects['context']->getId()
        ));
        $this->_objects['sippeer2'] = new Voipmanager_Model_Asterisk_SipPeer(array(
            'id'                => Tinebase_Record_Abstract::generateUID(),
            'context_id'        => $this->_objects['context']->getId()
        ));
        
        $this->_objects['line'] = new Voipmanager_Model_Snom_Line(array(
            'id'                => Tinebase_Record_Abstract::generateUID(),
            'snomphone_id'      => $this->_objects['phone']->getId(),
            'asteriskline_id'   => $this->_objects['sippeer']->getId(),
            'linenumber'        => 1,
            'lineactive'        => 1
        ));
        $this->_objects['line1'] = new Voipmanager_Model_Snom_Line(array(
            'id'                => Tinebase_Record_Abstract::generateUID(),
            'snomphone_id'      => $this->_objects['phone1']->getId(),
            'asteriskline_id'   => $this->_objects['sippeer1']->getId(),
            'linenumber'        => 1,
            'lineactive'        => 1
        ));
        
        $this->_objects['line2'] = new Voipmanager_Model_Snom_Line(array(
            'id'                => Tinebase_Record_Abstract::generateUID(),
            'snomphone_id'      => $this->_objects['phone2']->getId(),
            'asteriskline_id'   => $this->_objects['sippeer2']->getId(),
            'linenumber'        => 1,
            'lineactive'        => 1
        ));
        
        // create phone, location, template, rights
        $phoneBackend               = new Voipmanager_Backend_Snom_Phone(array(
            'modelName' => 'Phone_Model_MyPhone'
        ));
        $snomLocationBackend        = new Voipmanager_Backend_Snom_Location();
        $snomSettingBackend         = new Voipmanager_Backend_Snom_Setting();
        $snomPhoneSettingBackend    = new Voipmanager_Backend_Snom_PhoneSettings();
        $snomTemplateBackend        = new Voipmanager_Backend_Snom_Template();
        $snomSoftwareBackend        = new Voipmanager_Backend_Snom_Software();
        $snomLineBackend            = new Voipmanager_Backend_Snom_Line();
        $asteriskSipPeerBackend     = new Voipmanager_Backend_Asterisk_SipPeer();
        $asteriskContextBackend     = new Voipmanager_Backend_Asterisk_Context();
        
        $snomSoftwareBackend->create($this->_objects['software']);
        $snomLocationBackend->create($this->_objects['location']);
        $snomSettingBackend->create($this->_objects['setting']);
        $snomTemplateBackend->create($this->_objects['template']);
        
        $phoneBackend->create($this->_objects['phone']);
        $phoneBackend->create($this->_objects['phone1']);
        $phoneBackend->create($this->_objects['phone2']);
        
        $phoneBackend->setPhoneRights($this->_objects['phone']);
        $phoneBackend->setPhoneRights($this->_objects['phone1']);
        $phoneBackend->setPhoneRights($this->_objects['phone2']);
        
        $asteriskContextBackend->create($this->_objects['context']);
        
        $asteriskSipPeerBackend->create($this->_objects['sippeer']);
        $asteriskSipPeerBackend->create($this->_objects['sippeer1']);
        $asteriskSipPeerBackend->create($this->_objects['sippeer2']);
        
        $snomLineBackend->create($this->_objects['line']);
        $snomLineBackend->create($this->_objects['line1']);
        $snomLineBackend->create($this->_objects['line2']);
        
        $snomPhoneSettingBackend->create($this->_objects['phonesetting']);
        $snomPhoneSettingBackend->create($this->_objects['phonesetting1']);
        $snomPhoneSettingBackend->create($this->_objects['phonesetting2']);
        
        /******************** call history *************************/

        $phoneController = Phone_Controller::getInstance();
        
        $this->_objects['call1'] = new Phone_Model_Call(array(
            'id'                    => 'phpunitcallhistoryid1',
            'line_id'               => $this->_objects['line']->getId(),
            'phone_id'              => $this->_objects['phone']->getId(),
            'direction'             => Phone_Model_Call::TYPE_INCOMING,
            'source'                => '26',
            'destination'           => '0406437435',
        ));

        $this->_objects['call2'] = new Phone_Model_Call(array(
            'id'                    => 'phpunitcallhistoryid2',
            'line_id'               => $this->_objects['line']->getId(),
            'phone_id'              => $this->_objects['phone']->getId(),
            'direction'             => Phone_Model_Call::TYPE_INCOMING,
            'source'                => '26',
            'destination'           => '050364354',
        ));
        
        $this->_objects['call2a'] = new Phone_Model_Call(array(
            'id'                    => 'phpunitcallhistoryid2a',
            'line_id'               => $this->_objects['line1']->getId(),
            'phone_id'              => $this->_objects['phone1']->getId(),
            'direction'             => Phone_Model_Call::TYPE_INCOMING,
            'source'                => '27',
            'destination'           => '050364354',
        ));
        
        $this->_objects['call3'] = new Phone_Model_Call(array(
            'id'                    => 'phpunitcallhistoryid3',
            'line_id'               => $this->_objects['line2']->getId(),
            'phone_id'              => $this->_objects['phone2']->getId(),
            'direction'             => Phone_Model_Call::TYPE_INCOMING,
            'source'                => '78',
            'destination'           => '050998877',
        ));

        $scleverContact = Addressbook_Controller_Contact::getInstance()->getContactByUserId($this->_personas['sclever']->getId());
        $this->_objects['call4'] = new Phone_Model_Call(array(
            'id'                    => 'phpunitcallhistoryid4',
            'line_id'               => $this->_objects['line2']->getId(),
            'phone_id'              => $this->_objects['phone']->getId(),
            'direction'             => Phone_Model_Call::TYPE_INCOMING,
            'source'                => '78',
            'destination'           => $scleverContact->tel_work,
        ));
        
        $this->_objects['paging'] = array(
            'start' => 0,
            'limit' => 50,
            'sort' => 'start',
            'dir' => 'ASC',
        );
        
        $this->_objects['filter1'] = array(
            array('field' => 'query', 'operator' => 'contains', 'value' => '')
        );

        $this->_objects['filter2'] = array(
            array('field' => 'query', 'operator' => 'contains', 'value' => '05036'),
            array('field' => 'phone_id', 'operator' => 'AND', 'value' => array(array('field' => ':id', 'operator' => 'equals', 'value' => $this->_objects['phone1']->getId())))
        );
        
        $this->_objects['filter2a'] = array(
            array('field' => 'query', 'operator' => 'contains', 'value' => '05036'),
            array('field' => 'phone_id', 'operator' => 'AND', 'value' => array(array('field' => ':id', 'operator' => 'equals', 'value' => $this->_objects['phone1']->getId())))
        );

        $this->_objects['filter2b'] = array(
            array('field' => 'destination', 'operator' => 'contains', 'value' => '05036')
        );

        $this->_objects['filter3'] = array(
            array('field' => 'phone_id', 'operator' => 'AND', 'value' => array(array('field' => ':id', 'operator' => 'equals', 'value' => $this->_objects['phone']->getId())))
        );
        
        $this->_objects['filter4'] = array(
            array('field' => 'phone_id', 'operator' => 'AND', 'value' =>
                array(array('field' => ':id', 'operator' => 'equals', 'value' =>
                    $this->_objects['phone2']->getId())))
        );
        
        $this->_objects['filter5'] = array(
            array('field' => 'query', 'operator' => 'contains', 'value' => '998877')
        );
        
        // create calls
        $phoneController->callStarted($this->_objects['call1']);
        $phoneController->callStarted($this->_objects['call2']);
        $phoneController->callStarted($this->_objects['call2a']);
        $phoneController->callStarted($this->_objects['call3']);

        // create with normal controller to make sure we get contact relation
        Phone_Controller_Call::getInstance()->create($this->_objects['call4']);
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        parent::tearDown();
        
        if ($this->_adminUser != Tinebase_Core::getUser()) {
            Tinebase_Core::set(Tinebase_Core::USER, $this->_adminUser);
        }
    }
    
    /**
     * try to get all calls
     */
    public function testGetCalls()
    {
        // search calls without phone_id filter -> at least one call will be returned
        $result = $this->_json->searchCalls($this->_objects['filter1'], $this->_objects['paging']);
        $this->assertGreaterThanOrEqual(1, $result['totalcount']);
        $this->assertLessThanOrEqual(3, $result['totalcount']);
        
        // search query -> '05036' -> the user has made 2 calls each with another phone, another made one call, 1 is correct then
        $result = $this->_json->searchCalls($this->_objects['filter2'], $this->_objects['paging']);
        $this->assertEquals(1, $result['totalcount'], 'query filter not working');
        
        $result = $this->_json->searchCalls($this->_objects['filter2b'], $this->_objects['paging']);
        $this->assertEquals(1, $result['totalcount'], 'destination filter not working');
        
        // search for phone_id
        $result = $this->_json->searchCalls($this->_objects['filter3'], $this->_objects['paging']);
        $this->assertGreaterThan(1, $result['totalcount'], 'phone_id filter not working');
        
        $result = $this->_json->searchCalls($this->_objects['filter4'], $this->_objects['paging']);
        // the user searches for a phone not belonging to him, so no results will be returned
        $this->assertEquals(0, $result['totalcount'], 'calls of another user must not be found!');
        
        $result = $this->_json->searchCalls($this->_objects['filter2a'], $this->_objects['paging']);
        $this->assertEquals($this->_objects['phone1']->getId(), $result['results'][0]['phone_id']['id']);
        $this->assertEquals(1, $result['totalcount'], 'the user made one call with this phone!');
        $this->assertEquals($this->_objects['phone1']->getId(), $result['results'][0]['phone_id']['id']);
        $result = $this->_json->searchCalls($this->_objects['filter5'], $this->_objects['paging']);
        
        $this->assertEquals(0, $result['totalcount'], 'calls of another user must not be found!');
        $this->assertEquals('998877', $result['filter'][0]['value'], 'the filter should stay!');
    }

    /**
     * #8380: write a test for saveMyPhone as unprivileged user
     * https://forge.tine20.org/mantisbt/view.php?id=8380
     */
    public function testSaveMyPhoneAsUnprivilegedUser()
    {
        // first save the phone as privileged user
        $userPhone = $this->_json->getMyPhone($this->_objects['phone']->getId());
        $userPhone['lines'][0]['asteriskline_id']['cfi_mode'] = "number";
        $userPhone['lines'][0]['asteriskline_id']['cfi_number'] = "+494949302111";
        
        // try to set a property which should be overwritten again
        $userPhone['description'] = 'no phone';
        
        $phone = $this->_json->saveMyPhone($userPhone);
        
        $this->assertEquals('number', $phone['lines'][0]['asteriskline_id']['cfi_mode']);
        $this->assertEquals('+494949302111', $phone['lines'][0]['asteriskline_id']['cfi_number']);
        $this->assertEquals('user phone', $phone['description']);
        
        $additionalLine = array(
            'id'                => Tinebase_Record_Abstract::generateUID(),
            'snomphone_id'      => $this->_objects['phone']->getId(),
            'asteriskline_id'   => $this->_objects['sippeer']->getId(),
            'linenumber'        => 2,
            'lineactive'        => 2
        );
        
        // use another user which doesn't have access to the phone
        Tinebase_Core::set(Tinebase_Core::USER, Tinebase_User::getInstance()->getFullUserByLoginName('pwulf'));
        
        $e = new Exception('No Exception has been thrown!');
        
        try {
            $this->_json->saveMyPhone($userPhone);
        } catch (Exception $e) {
            
        }
        
        $this->assertEquals('Tinebase_Exception_AccessDenied', get_class($e));
        
        // try to save with a line removed
        Tinebase_Core::set(Tinebase_Core::USER, $this->_adminUser);
        $snomLineBackend = new Voipmanager_Backend_Snom_Line();
        $snomLineBackend->create(new Voipmanager_Model_Snom_Line($additionalLine));
        $userPhone = $this->_json->getMyPhone($this->_objects['phone']->getId());

        unset($userPhone['lines'][1]);
        $this->setExpectedException('Tinebase_Exception_AccessDenied');
        $this->_json->saveMyPhone($userPhone);
    }
    
    /**
     * get and update user phone
     * 
     * @return void
     */
    public function testGetUpdateSnomPhone()
    {
        $userPhone = $this->_json->getMyPhone($this->_objects['phone']->getId());
        
        $this->assertEquals('user phone', $userPhone['description'], 'no description');
        $this->assertTrue((isset($userPhone['web_language']) || array_key_exists('web_language', $userPhone)), 'missing web_language:' . print_r($userPhone, TRUE));
        $this->assertEquals('English', $userPhone['web_language'], 'wrong web_language');
        $this->assertGreaterThan(0, count($userPhone['lines']), 'no lines attached');

        // update phone
        $userPhone['web_language'] = 'Deutsch';
        $userPhone['lines'][0]['idletext'] = 'idle';
        $userPhone['lines'][0]['asteriskline_id']['cfd_time'] = 60;
        $userPhoneUpdated = $this->_json->saveMyPhone($userPhone);
        
        $this->assertEquals('Deutsch', $userPhoneUpdated['web_language'], 'no updated web_language');
        $this->assertEquals('idle', $userPhoneUpdated['lines'][0]['idletext'], 'no updated idletext');
        $this->assertEquals(60, $userPhoneUpdated['lines'][0]['asteriskline_id']['cfd_time'], 'no updated cfd time');
    }
    
    /**
     * try to get registry data
     */
    public function testGetRegistryData()
    {
        // get phone json
        $data = $this->_json->getRegistryData();
        
        $this->assertGreaterThan(0, count($data['Phones']), 'more than 1 phone expected');
        $this->assertGreaterThan(0, count($data['Phones'][0]['lines']), 'no lines attached');
        $this->assertStringEndsWith('user phone', $data['Phones'][0]['description'], 'no description');
    }
    
    // TODO we need some mocks for asterisk backends...
    public function _testDialNumber()
    {
        $number = '+494031703167';
        $phoneId = $this->_objects['phone']->getId();
        $lineId = $this->_objects['line']->getId();
        
        $status = $this->_json->dialNumber($number, $phoneId, $lineId);
        
        $this->assertEquals('array', gettype($status));
        $this->assertTrue((isset($status['success']) || array_key_exists('success', $status)));
        $this->assertTrue($status['success']);
    }

    /**
     * @see 0011934: show contacts in phone call grid
     */
    public function testContactId()
    {
        // search phone 2 calls (on should be linked to sclever)
        $result = $this->_json->searchCalls($this->_objects['filter3'], $this->_objects['paging']);

        $scleverCall = null;
        foreach ($result['results'] as $call) {
            if ($call['id'] == 'phpunitcallhistoryid4') {
                $scleverCall = $call;
            }
        }

        $this->assertTrue($scleverCall !== null);
        $this->assertTrue(isset($scleverCall['contact_id']));
        $this->assertEquals($this->_personas['sclever']->getId(), $scleverCall['contact_id']['account_id'], print_r($scleverCall['contact_id'], true));
    }
}
