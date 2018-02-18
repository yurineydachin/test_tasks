<?php

use PHPUnit\Framework\TestCase;

class PathFinderTest extends TestCase {
    private $boardingCards = [
        [
            "type" => "taxi",
            "from" => "Balashiha",
            "to"   => "Moscow",
            "text" => "",
        ],
        [
            "type" => "Flight SU1480",
            "from" => "Shanghai",
            "to"   => "Guanzhou",
            "text" => "Seat 5D",
        ],
        [
            "type" => "Flight ABC",
            "from" => "Guanzhou",
            "to"   => "Hangzhou",
            "text" => "",
        ],
        [
            "type" => "taxi",
            "from" => "Hangzhou",
            "to"   => "HZ Citadiens",
            "text" => "",
        ],
        [
            "type" => "Flight SU1480",
            "from" => "Moscow",
            "to"   => "Shanghai",
            "text" => "Gate 45, Seat 15C, you may take only 1 luggage piece",
        ],
        [
            "type" => "Airport bus",
            "from" => "Shanghai",
            "to"   => "Hangzhou",
            "text" => "Seat 5",
        ],
        [
            "type" => "taxi",
            "from" => "Hangzhou",
            "to"   => "HZ Citadiens",
            "text" => "",
        ],
        [
            "type" => "Work shatl",
            "from" => "HZ Citadiens",
            "to"   => "HZ LeJa campus",
            "text" => "",
        ],
        [
            "type" => "taxi",
            "from" => "HZ LeJa campus",
            "to"   => "HZ Citadiens",
            "text" => "",
        ],
        [
            "type" => "taxi",
            "from" => "HZ Citadiens",
            "to"   => "HZ Oakwood",
            "text" => "",
        ],
        [
            "type" => "walking",
            "from" => "HZ Oakwood",
            "to"   => "HZ Basement",
            "text" => "",
        ],
        [
            "type" => "taxi",
            "from" => "HZ Basement",
            "to"   => "HZ Eudora",
            "text" => "",
        ],
        [
            "type" => "taxi",
            "from" => "HZ Eudora",
            "to"   => "HZ Citadiens",
            "text" => "",
        ],
        [
            "type" => "walking",
            "from" => "HZ Citadiens",
            "to"   => "HZ Wallmart",
            "text" => "",
        ],
        [
            "type" => "walking",
            "from" => "HZ Wallmart",
            "to"   => "HZ Park",
            "text" => "",
        ],
        [
            "type" => "walking",
            "from" => "HZ Wallmart",
            "to"   => "HZ Park",
            "text" => "",
        ],
        [
            "type" => "undergroud",
            "from" => "HZ Park",
            "to"   => "HZ Citadiens",
            "text" => "",
        ],
        [
            "type" => "taxi",
            "from" => "HZ Citadiens",
            "to"   => "Hangzhou",
            "text" => "",
        ],
        [
            "type" => "Airport bus",
            "from" => "Hangzhou",
            "to"   => "Shanghai",
            "text" => "Seat 8",
        ],
        [
            "type" => "Flight SU4041",
            "from" => "Shanghai",
            "to"   => "Moscow",
            "text" => "Gate 22, Seat 72F, luggage 2PC",
        ],
    ];

    public function testPathFound()
    {
        $pathFinder = new PathFinder(new PointsRegistry($this->boardingCards));
        try {
            $this->assertEquals(" -> Shanghai -> Guanzhou", $pathFinder->searchPath("Moscow", "Guanzhou")->__toString());
        } catch (PathFilderException $e) {
            $this->fail($e->getMessage()); 
        }
    }

    public function testPathFound2()
    {
        $pathFinder = new PathFinder(new PointsRegistry($this->boardingCards));
        try {
            $this->assertEquals(" -> Moscow -> Shanghai -> Guanzhou", $pathFinder->searchPath("Balashiha", "Guanzhou")->__toString());
        } catch (PathFilderException $e) {
            $this->fail($e->getMessage()); 
        }
    }

    public function testLongPathFound()
    {
        $pathFinder = new PathFinder(new PointsRegistry($this->boardingCards));
        try {
            $this->assertEquals(" -> Moscow -> Shanghai -> Hangzhou -> HZ Citadiens", $pathFinder->searchPath("Balashiha", "HZ Citadiens")->__toString());
        } catch (PathFilderException $e) {
            $this->fail($e->getMessage()); 
        }
    }

    public function testPathNotFound()
    {
        $pathFinder = new PathFinder(new PointsRegistry($this->boardingCards));
        try {
            $route = $pathFinder->searchPath("Guanzhou", "Balashiha");
            $this->fail("route found: " . $route); 
        } catch (PathFilderException $e) {
            $this->assertEquals("Route not found: ", $e->getMessage());
        }
    }

    public function testBackPathFound()
    {
        $pathFinder = new PathFinder(new PointsRegistry($this->boardingCards));
        try {
            $this->assertEquals(" -> Hangzhou -> Shanghai -> Moscow", $pathFinder->searchPath("HZ Citadiens", "Moscow")->__toString());
        } catch (PathFilderException $e) {
            $this->fail($e->getMessage()); 
        }
    }
}

