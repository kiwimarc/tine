<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     SimpleFAQ
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

class SimpleFAQ_AllTests
{
    public static function main ()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }
    
    public static function suite ()
    {
        $suite = new \PHPUnit\Framework\TestSuite('Tine 2.0 SimpleFAQ All Tests');
        $suite->addTestSuite('SimpleFAQ_JsonTest');
        return $suite;
    }
}
