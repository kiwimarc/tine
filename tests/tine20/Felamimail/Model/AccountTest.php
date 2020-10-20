<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * 
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Felamimail_Model_AccountTest
 */
class Felamimail_Model_AccountTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new \PHPUnit\Framework\TestSuite('Tine 2.0 Felamimail Account Model Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp(): void
{
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown(): void
{
    }

    /********************************* test funcs *************************************/
    
    /**
     * test get smtp config
     */
    public function testGetImapConfig()
    {
        $this->markTestSkipped('this test has to be implemented yet');
    }
    
    /**
     * test get smtp config
     */
    public function testGetSmtpConfig()
    {
        $this->markTestSkipped('this test has to be refactored');
        
        $smtpConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::SMTP, new Tinebase_Config_Struct())->toArray();
        
        $account = new Felamimail_Model_Account(array(
            'type'      => Felamimail_Model_Account::TYPE_SYSTEM,
        ));
        $accountSmtpConfig = $account->getSmtpConfig();
        
        if ((isset($smtpConfig['primarydomain']) || array_key_exists('primarydomain', $smtpConfig))) {
            $this->assertStringContainsString($smtpConfig['primarydomain'], $accountSmtpConfig['username']);
        }
        
        if (TestServer::getInstance()->getConfig()->mailserver) {
            $this->assertEquals(TestServer::getInstance()->getConfig()->mailserver, $accountSmtpConfig['hostname']);
        }
    }
}
