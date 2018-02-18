<?php

use PHPUnit\Framework\TestCase;

class RouteTest extends TestCase {

    public function testToString()
    {
        $p1 = new Point("Moscow");
        $p2 = new Point("Shanghai");
        $p3 = new Point("Hangzhou");

        $bc1 = new BoardingCard($p2, "Flight SU141", "Gate 31, Seat 43A, any luggage free)");
        $p1->addCard($bc1);

        $bc2 = new BoardingCard($p3, "Airport bus 332", "Seat 18");
        $p2->addCard($bc2);

        $route1 = new Route($bc1);
        $this->assertEquals(" -> Shanghai", $route1->__toString());

        $route2 = new Route($bc2, $route1);
        $this->assertEquals(" -> Shanghai -> Hangzhou", $route2->__toString());
        $this->assertEquals([
            "Take Flight SU141 to Shanghai. Gate 31, Seat 43A, any luggage free)",
            "Take Airport bus 332 to Hangzhou. Seat 18"
        ], $route2->getPrintInfo());
    }
}
