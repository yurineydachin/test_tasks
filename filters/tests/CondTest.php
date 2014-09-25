<?php
require_once LIBRARY_PATH.'/phpunit/ControllerTest.php';
require_once APPLICATION_PATH."/common/models/Filter/Cond.php";
 
class CondTest extends Zend_Test_PHPUnit_ControllerTestCase
{
    // setUp выполняется перед каждым тестом
    protected function setUp()
    {
        parent::setUp();
    }

    public function testEmpty()
    {
        $cond = TestCond::create();
        $this->assertTrue($cond instanceof Cond);
        $this->assertEquals($cond->getName(),  null);
        $this->assertEquals($cond->getOp(),    Cond::OP_DEFAULT);
        $this->assertEquals($cond->getValue(), null);
    }

    public function testStart()
    {
        $cond = TestCond::create('name', Cond::OP_NOT_EQUAL, 'str');
        $this->assertEquals($cond->getName(),  'name');
        $this->assertEquals($cond->getOp(),    Cond::OP_NOT_EQUAL);
        $this->assertEquals($cond->getValue(), 'str');
    }

    public function testUnsupportedOp()
    {
        try
        {
            $cond = TestCond::create('name', 'operation', 'str');
            $this->assertFalse('CondOpException');
        }
        catch (CondOpException $e)
        {
            $this->assertTrue(true);
        }
    }

    public function testChain()
    {
        $cond = TestCond::create()->setName('name')->setOp(Cond::OP_NOT_EQUAL)->setValue('str');
        $this->assertEquals($cond->getName(),  'name');
        $this->assertEquals($cond->getOp(),    Cond::OP_NOT_EQUAL);
        $this->assertEquals($cond->getValue(), 'str');
    }

    public function testChangeOpToEqual()
    {
        $cond = TestCond::create('name', Cond::OP_IN, 'str');
        $this->assertEquals($cond->getName(),  'name');
        $this->assertEquals($cond->getOp(),    Cond::OP_EQUAL);
        $this->assertEquals($cond->getValue(), 'str');
    }

    public function testCheckType()
    {
        $cond = TestCond::create('name', Cond::OP_EQUAL, 0);
        $this->assertEquals($cond->getName(),  'name');
        $this->assertEquals($cond->getOp(),    Cond::OP_EQUAL);
        $this->assertTrue($cond->getValue() === 0);
    }

    public function testChangeOpToIn()
    {
        $cond = TestCond::create('name', Cond::OP_EQUAL, array(1,2,3));
        $this->assertEquals($cond->getName(),  'name');
        $this->assertEquals($cond->getOp(),    Cond::OP_IN);
        $this->assertEquals($cond->getValue(), array(1,2,3));
    }

    public function testArrayOneValue()
    {
        $cond = TestCond::create('name', Cond::OP_IN, array(10));
        $this->assertEquals($cond->getName(),  'name');
        $this->assertEquals($cond->getOp(),    Cond::OP_EQUAL);
        $this->assertEquals($cond->getValue(), 10);
    }

    public function testArrayNullValue()
    {
        $cond = TestCond::create('name', Cond::OP_IN, array());
        $this->assertEquals($cond->getName(),  'name');
        $this->assertEquals($cond->getOp(),    Cond::OP_EQUAL);
        $this->assertTrue(is_null($cond->getValue()));
    }

    public function testOption()
    {
        $cond = TestCond::create()->addOption(Cond::OPTION_LOWER);
        $this->assertEquals($cond->getOptions(), array(Cond::OPTION_LOWER => Cond::OPTION_LOWER));
    }
}

class TestCond extends Cond
{
}
