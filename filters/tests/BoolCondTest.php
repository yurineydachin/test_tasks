<?php
require_once LIBRARY_PATH.'/phpunit/ControllerTest.php';
require_once APPLICATION_PATH."/common/models/Filter/BoolCond.php";
 
class BoolCondTest extends Zend_Test_PHPUnit_ControllerTestCase
{
    public function testEmpty()
    {
        $cond = BoolCond::create();
        $this->assertTrue($cond instanceof BoolCond);
        $this->assertEquals($cond->getName(),  null);
        $this->assertEquals($cond->getOp(),    Cond::OP_DEFAULT);
        $this->assertEquals($cond->getValue(), null);
    }

    public function testStart()
    {
        $cond = BoolCond::create('name', Cond::OP_NOT_EQUAL, true);
        $this->assertEquals($cond->getName(),  'name');
        $this->assertEquals($cond->getOp(),    Cond::OP_NOT_EQUAL);
        $this->assertTrue($cond->getValue());
    }

    public function testNotBoolValue()
    {
        $cond = BoolCond::create('name', Cond::OP_EQUAL, array());
        $this->assertEquals($cond->getName(),  'name');
        $this->assertEquals($cond->getOp(),    Cond::OP_EQUAL);
        $this->assertFalse($cond->getValue());
    }
}
