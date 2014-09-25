<?php
require_once LIBRARY_PATH.'/phpunit/ControllerTest.php';
require_once APPLICATION_PATH."/common/models/Filter/IntCond.php";
 
class IntCondTest extends Zend_Test_PHPUnit_ControllerTestCase
{
    public function testEmpty()
    {
        $cond = IntCond::create();
        $this->assertTrue($cond instanceof IntCond);
        $this->assertEquals($cond->getName(),  null);
        $this->assertEquals($cond->getOp(),    Cond::OP_DEFAULT);
        $this->assertEquals($cond->getValue(), null);
    }

    public function testStart()
    {
        $cond = IntCond::create('name', Cond::OP_NOT_EQUAL, 10);
        $this->assertEquals($cond->getName(),  'name');
        $this->assertEquals($cond->getOp(),    Cond::OP_NOT_EQUAL);
        $this->assertEquals($cond->getValue(), 10);
    }

    public function testNotIntValue()
    {
        $cond = IntCond::create('name', Cond::OP_LESS, 'str');
        $this->assertEquals($cond->getName(),  'name');
        $this->assertEquals($cond->getOp(),    Cond::OP_LESS);
        $this->assertTrue(is_null($cond->getValue()));
    }

    public function testCheckType()
    {
        $cond = IntCond::create('name', Cond::OP_EQUAL, 0);
        $this->assertEquals($cond->getName(),  'name');
        $this->assertEquals($cond->getOp(),    Cond::OP_EQUAL);
        $this->assertTrue($cond->getValue() === 0);
        $this->assertFalse(is_null($cond->getValue()));
    }
}
