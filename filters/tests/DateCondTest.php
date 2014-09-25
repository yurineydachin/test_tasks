<?php
require_once LIBRARY_PATH.'/phpunit/ControllerTest.php';
require_once APPLICATION_PATH."/common/models/Filter/DateCond.php";
 
class DateCondTest extends Zend_Test_PHPUnit_ControllerTestCase
{
    public function testEmpty()
    {
        $cond = DateCond::create();
        $this->assertTrue($cond instanceof DateCond);
        $this->assertEquals($cond->getName(),  null);
        $this->assertEquals($cond->getOp(),    Cond::OP_DEFAULT);
        $this->assertEquals($cond->getValue(), null);
    }

    public function testStart()
    {
        $cond = DateCond::create('name', Cond::OP_NOT_EQUAL, '2013-06-01');
        $this->assertEquals($cond->getName(),  'name');
        $this->assertEquals($cond->getOp(),    Cond::OP_NOT_EQUAL);
        $this->assertEquals($cond->getValue()->format('Y-m-d'), '2013-06-01');
    }

    public function testNotDateValue()
    {
        try {
            $cond = DateCond::create('name', Cond::OP_LESS, 'str');
            $this->assertFalse('CondDateException');
        } catch (CondDateException $e) {
            $this->assertTrue(true);
        }
    }
}
