<?php

use PHPUnit\Framework\TestCase;

class BoardingCardTest extends TestCase {

    public function testGetToName()
    {
        $p = new Point("name1");
        $bc = new BoardingCard($p);
        $this->assertEquals("name1", $bc->getToName());
    }
}

