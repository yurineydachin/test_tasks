<?php
require_once LIBRARY_PATH.'/phpunit/ControllerTest.php';
require_once APPLICATION_PATH."/common/models/Filter/JoinCond.php";
 
class JoinCondTest extends Zend_Test_PHPUnit_ControllerTestCase
{
    public function testEmpty()
    {
        $cond = JoinCond::create();
        $this->assertTrue($cond instanceof JoinCond);
        $this->assertEquals($cond->getName(),  null);
        $this->assertEquals($cond->getOp(),    Cond::OP_AND);
        $this->assertEquals($cond->getValue(), array());
    }

    public function testStart()
    {
        $cond = JoinCond::create(
            StrCond::create('name1', Cond::OP_EQUAL, 'str'),
            Cond::OP_AND,
            IntCond::create('name2', Cond::OP_LESS, 10)
        );
        $this->assertTrue(is_null($cond->getName()));
        $this->assertEquals($cond->getOp(), Cond::OP_AND);
        $val = $cond->getValue();
        $this->assertEquals(count($val), 2);
        $this->assertEquals($val[0]->getName(), 'name1');
        $this->assertEquals($val[1]->getName(), 'name2');
    }

    public function testManyValues()
    {
        $cond = JoinCond::create();
        $cond->add(StrCond::create( 'name1', Cond::OP_EQUAL, 'str'));
        $cond->add(IntCond::create( 'name2', Cond::OP_LESS, 10));
        $cond->add(StrCond::create('name3', Cond::OP_IN, array('str', 10)));

        $val = $cond->getValue();
        $this->assertEquals(count($val), 3);
        $this->assertEquals($val[0]->getName(), 'name1');
        $this->assertEquals($val[1]->getName(), 'name2');
        $this->assertEquals($val[2]->getName(), 'name3');
        $this->assertEquals($cond->getOp(), Cond::OP_AND);
    }

    public function testUnsupportedValues()
    {
        $cond = JoinCond::create();
        try {
            $cond->add('str-value');
            $this->assertFalse('CondValueException');
        } catch (CondValueException $e) {
            $this->assertTrue(true);
        }
    }
}
