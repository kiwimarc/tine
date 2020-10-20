<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * 
 * @todo        move setup tests to separate test suite with special Setup_TestServer
 * @todo        repair Setup_Backend_AllTests and add them again
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

class Setup_AllTests
{
    public static function main ()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }
    
    public static function suite ()
    {
        $suite = new \PHPUnit\Framework\TestSuite('Tine 2.0 Setup All Tests');
        $suite->addTestSuite('Setup_CoreTest');
        $suite->addTestSuite('Setup_ControllerTest');
        $suite->addTestSuite('Setup_JsonTest');
        $suite->addTestSuite('Setup_CliTest');
        // @todo there seems to be some unbuffered queries here, fix them!
        //$suite->addTestSuite('Setup_Backend_AllTests');
        return $suite;
    }
}
