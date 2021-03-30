<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2007-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Matthias Greiling <m.greiling@metaways.de>
 */

class Tinebase_Frontend_AllTests
{
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new \PHPUnit\Framework\TestSuite('Tine 2.0 Tinebase All Frontend Tests');
        $suite->addTestSuite(Tinebase_Frontend_Json_ContainerTest::class);
        $suite->addTestSuite(Tinebase_Frontend_Json_PersistentFilterTest::class);
        $suite->addTestSuite(Tinebase_Frontend_JsonTest::class);
        // FIXME has to run after \Tinebase_Frontend_JsonTest::testGetAllRegistryData because that fails otherwise ("currentAccount is missing")
        $suite->addTestSuite(Tinebase_Frontend_Json_AreaLockTest::class);
        $suite->addTestSuite(Tinebase_Frontend_CliTest::class);
        $suite->addTestSuite(Tinebase_Frontend_HttpTest::class);
        $suite->addTestSuite(Tinebase_Frontend_Http_SinglePageApplicationTest::class);
        $suite->addTestSuite(Tinebase_Frontend_WebDAV_RecordTest::class);
        
        return $suite;
    }
}
