<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     OpenDocument
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2012-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

// TODO: remove this after opendocument and timezone convert has been outsourced
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'DocumentTests.php';

class OpenDocument_AllTests
{
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }
    
    public static function suite ()
    {
        $suite = new \PHPUnit\Framework\TestSuite('Tine 2.0 OpenDocument All Tests');
        $suite->addTestSuite('OpenDocument_DocumentTests');
        return $suite;
    }
}
