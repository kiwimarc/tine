<?php
class CiTestSuite4
{
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Tine 2.0 all server tests');

        $suite->addTestSuite(Calendar_AllTests::class);
        $suite->addTestSuite(OpenDocument_AllTests::class);

        return $suite;
    }
}