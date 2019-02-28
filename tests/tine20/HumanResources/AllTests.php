<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     HumanResources
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2012-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

class HumanResources_AllTests
{
    public static function suite ()
    {
        $suite = new PHPUnit_Framework_TestSuite('Tine 2.0 HumanResources All Tests');
        $suite->addTestSuite(HumanResources_JsonTests::class);
        $suite->addTestSuite(HumanResources_CliTests::class);
        $suite->addTestSuite(HumanResources_Controller_ContractTests::class);
        $suite->addTestSuite(HumanResources_Controller_EmployeeTests::class);
        $suite->addTestSuite(HumanResources_ModelConfigurationTest::class);
        $suite->addTestSuite(HumanResources_Import_DemoDataTest::class);
        return $suite;
    }
}
