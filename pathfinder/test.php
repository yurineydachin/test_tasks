<?php

use PHPUnit\Framework\TestSuite;
require_once 'autoloader.php';

class AllTests
{
    public static function suite()
    {
        $suite = new TestSuite('AllMySuite');
        $suite->addTestSuite('PointTest'); 
        $suite->addTestSuite('PointsRegistryTest'); 
        $suite->addTestSuite('BoardingCardTest'); 
        $suite->addTestSuite('PathFinderTest'); 
        $suite->addTestSuite('RouteTest'); 
        return $suite; 
    }
}
