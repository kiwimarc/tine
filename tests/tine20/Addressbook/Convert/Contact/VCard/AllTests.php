<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2011-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(dirname(dirname(__FILE__))))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

class Addressbook_Convert_Contact_VCard_AllTests
{
    public static function main ()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }
    
    public static function suite ()
    {
        $suite = new \PHPUnit\Framework\TestSuite('Tine 2.0 Addressbook All Import Vcard Tests');
        $suite->addTestSuite('Addressbook_Convert_Contact_VCard_FactoryTest');
        $suite->addTestSuite('Addressbook_Convert_Contact_VCard_GenericTest');
        $suite->addTestSuite('Addressbook_Convert_Contact_VCard_TelefonbuchTest');
        $suite->addTestSuite('Addressbook_Convert_Contact_VCard_IOSTest');
        $suite->addTestSuite('Addressbook_Convert_Contact_VCard_MacOSXTest');
        $suite->addTestSuite('Addressbook_Convert_Contact_VCard_SogoTest');
        $suite->addTestSuite('Addressbook_Convert_Contact_VCard_EMClientTest');
        $suite->addTestSuite('Addressbook_Convert_Contact_VCard_WebDAVCollaboratorTest');
        
        return $suite;
    }
}
