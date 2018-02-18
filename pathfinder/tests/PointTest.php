<?php

use PHPUnit\Framework\TestCase;

class PointTest extends TestCase {

    public function testGetName()
    {
        $p = new Point("name1");
        $this->assertEquals("name1", $p->getName());
    }
}
