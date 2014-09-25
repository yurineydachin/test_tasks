<?php
require_once LIBRARY_PATH.'/phpunit/ControllerTest.php';
require_once APPLICATION_PATH."/common/models/Filter/StrCond.php";
 
class StrCondTest extends Zend_Test_PHPUnit_ControllerTestCase
{
    public function testEmpty()
    {
        $cond = StrCond::create();
        $this->assertTrue($cond instanceof StrCond);
        $this->assertEquals($cond->getName(),  null);
        $this->assertEquals($cond->getOp(),    Cond::OP_DEFAULT);
        $this->assertEquals($cond->getValue(), null);
    }

    public function testStart()
    {
        $cond = StrCond::create('name', Cond::OP_NOT_EQUAL, 10);
        $this->assertEquals($cond->getName(),  'name');
        $this->assertEquals($cond->getOp(),    Cond::OP_NOT_EQUAL);
        $this->assertTrue($cond->getValue() === '10');
    }

    public function testArrayValue()
    {
        $cond = StrCond::create('name', Cond::OP_LIKE, array(10,'str'));
        $this->assertEquals($cond->getName(),  'name');
        $this->assertEquals($cond->getOp(),    Cond::OP_IN);
        $this->assertEquals($cond->getValue(), array(10,'str'));
    }
}
