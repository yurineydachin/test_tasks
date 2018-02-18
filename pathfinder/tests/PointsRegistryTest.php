<?php

use PHPUnit\Framework\TestCase;

class PointsRegistryTest extends TestCase {

    public function testGetToName()
    {
        $boardingCards = [
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
        ];
        $registry = new PointsRegistry($boardingCards);

        $p1 = $registry->getByName("Moscow");
        $this->assertNotNull($p1);
        $this->assertEquals("Moscow", $p1->getName());
        $this->assertEquals(1, count($p1->getCards()));

        $p2 = $registry->getByName("Shanghai");
        $this->assertNotNull($p2);
        $this->assertEquals("Shanghai", $p2->getName());
        $this->assertEquals(1, count($p2->getCards()));

        $p3 = $registry->getByName("Hangzhou");
        $this->assertNotNull($p3);
        $this->assertEquals("Hangzhou", $p3->getName());
        $this->assertEquals(1, count($p3->getCards()));

        $p4 = $registry->getByName("HZ Citadiens");
        $this->assertNotNull($p4);
        $this->assertEquals("HZ Citadiens", $p4->getName());
        $this->assertEquals(0, count($p4->getCards()));
    }
}
