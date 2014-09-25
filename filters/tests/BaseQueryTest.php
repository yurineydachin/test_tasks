<?php
require_once LIBRARY_PATH.'/phpunit/ControllerTest.php';
require_once APPLICATION_PATH."/common/models/Filter/BaseQuery.php";
 
class BaseQueryTest extends Zend_Test_PHPUnit_ControllerTestCase
{
    // setUp выполняется перед каждым тестом
    protected function setUp()
    {
        parent::setUp();
    }

    public function testEmpty()
    {
        $query = BaseQuery::create();
        $this->assertTrue($query instanceof BaseQuery);
        $this->assertEquals($query->getParts(), array(
            BaseQuery::COLUMNS      => array(),
            BaseQuery::FROM         => null,
            BaseQuery::INNER_JOIN   => array(),
            BaseQuery::LEFT_JOIN    => array(),
            BaseQuery::WHERE        => null,
            BaseQuery::GROUP        => array(),
            BaseQuery::HAVING       => array(),
            BaseQuery::ORDER        => array(),
            BaseQuery::LIMIT        => null,
        ));
    }

    public function testFrom()
    {
        $query = BaseQuery::create()->from('orphea.objets', array('id_objet', 'titre_court'));
        $this->assertEquals($query->getParts(), array(
            BaseQuery::COLUMNS      => array(),
            BaseQuery::FROM         => array(
                'tableName'  => 'orphea.objets',
                'schemaName' => null,
                'cols' => array('id_objet', 'titre_court'),
            ),
            BaseQuery::INNER_JOIN   => array(),
            BaseQuery::LEFT_JOIN    => array(),
            BaseQuery::WHERE        => null,
            BaseQuery::GROUP        => array(),
            BaseQuery::HAVING       => array(),
            BaseQuery::ORDER        => array(),
            BaseQuery::LIMIT        => null,
        ));
    }

    public function testColumns()
    {
        $query = BaseQuery::create()->columns(array('id_objet', 'titre_court'));
        $this->assertEquals($query->getParts(), array(
            BaseQuery::COLUMNS      => array(
                array(
                    'tableName' => null,
                    'cols' => array('id_objet', 'titre_court'),
                ),
            ),
            BaseQuery::FROM         => null,
            BaseQuery::INNER_JOIN   => array(),
            BaseQuery::LEFT_JOIN    => array(),
            BaseQuery::WHERE        => null,
            BaseQuery::GROUP        => array(),
            BaseQuery::HAVING       => array(),
            BaseQuery::ORDER        => array(),
            BaseQuery::LIMIT        => null,
        ));
    }

    public function testJoin()
    {
        $query = BaseQuery::create()->joinInner('orphea.iptc', 'id=id_obj', array('id_objet', 'titre_court'));
        $this->assertEquals($query->getParts(), array(
            BaseQuery::COLUMNS      => array(),
            BaseQuery::FROM         => null,
            BaseQuery::INNER_JOIN   => array(
                'orphea.iptc' => array(
                    'tableName'  => 'orphea.iptc',
                    'schemaName' => null,
                    'cond' => 'id=id_obj',
                    'cols' => array('id_objet', 'titre_court'),
                ),
            ),
            BaseQuery::LEFT_JOIN    => array(),
            BaseQuery::WHERE        => null,
            BaseQuery::GROUP        => array(),
            BaseQuery::HAVING       => array(),
            BaseQuery::ORDER        => array(),
            BaseQuery::LIMIT        => null,
        ));
    }

    public function testWhereOne()
    {
        $query = BaseQuery::create()->where(
            StrCond::create('name', Cond::OP_LIKE, 'str%')
        );
        $cond = $query->getPart(BaseQuery::WHERE);
        $this->assertEquals($cond->getName(),  'name');
        $this->assertEquals($cond->getOp(),    Cond::OP_LIKE);
        $this->assertEquals($cond->getValue(), 'str%');
    }

    public function testWhereAnd()
    {
        $query = BaseQuery::create()
            ->where(StrCond::create('name1', Cond::OP_LIKE, 'str%'))
            ->where(IntCond::create('name2', Cond::OP_IN, array(1,2)));

        $cond = $query->getPart(BaseQuery::WHERE);
        $this->assertTrue(is_null($cond->getName()));
        $this->assertEquals($cond->getOp(), Cond::OP_AND);

        $val = $cond->getValue();
        $this->assertEquals(count($val), 2);
        $this->assertEquals($val[0]->getName(), 'name1');
        $this->assertEquals($val[1]->getName(), 'name2');
    }

    public function testWhereOrAnd()
    {
        $query = BaseQuery::create()
            ->where(StrCond::create('name1', Cond::OP_LIKE, 'str%'))
            ->where(IntCond::create('name2', Cond::OP_IN, array(1,2)))
            ->orWhere(BoolCond::create('name3', null, true))
            ->orWhere(DateCond::create('name4', Cond::OP_MORE, '2013-06-01'));

        $cond = $query->getPart(BaseQuery::WHERE);
        $this->assertTrue(is_null($cond->getName()));
        $this->assertEquals($cond->getOp(), Cond::OP_OR);

        $val = $cond->getValue();
        $this->assertEquals(count($val), 3);
        $this->assertTrue($val[0] instanceof JoinCond);
        $this->assertEquals($val[0]->getOp(), Cond::OP_AND);
        $val0 = $val[0]->getValue();
        $this->assertEquals($val0[0]->getName(), 'name1');
        $this->assertEquals($val0[1]->getName(), 'name2');

        $this->assertEquals($val[1]->getName(), 'name3');
        $this->assertEquals($val[2]->getName(), 'name4');
    }

    public function testGroup()
    {
        $query = BaseQuery::create()->group('id_objet');
        $this->assertEquals($query->getParts(), array(
            BaseQuery::COLUMNS      => array(),
            BaseQuery::FROM         => null,
            BaseQuery::INNER_JOIN   => array(),
            BaseQuery::LEFT_JOIN    => array(),
            BaseQuery::WHERE        => null,
            BaseQuery::GROUP        => array('id_objet'),
            BaseQuery::HAVING       => array(),
            BaseQuery::ORDER        => array(),
            BaseQuery::LIMIT        => null,
        ));
    }

    public function testHaving()
    {
        $query = BaseQuery::create()->having('id_objet = id_iptc');
        $this->assertEquals($query->getParts(), array(
            BaseQuery::COLUMNS      => array(),
            BaseQuery::FROM         => null,
            BaseQuery::INNER_JOIN   => array(),
            BaseQuery::LEFT_JOIN    => array(),
            BaseQuery::WHERE        => null,
            BaseQuery::GROUP        => array(),
            BaseQuery::HAVING       => array('id_objet = id_iptc'),
            BaseQuery::ORDER        => array(),
            BaseQuery::LIMIT        => null,
        ));
    }

    public function testOrder()
    {
        $query = BaseQuery::create()->order('id_objet', BaseQuery::DESC);
        $this->assertEquals($query->getParts(), array(
            BaseQuery::COLUMNS      => array(),
            BaseQuery::FROM         => null,
            BaseQuery::INNER_JOIN   => array(),
            BaseQuery::LEFT_JOIN    => array(),
            BaseQuery::WHERE        => null,
            BaseQuery::GROUP        => array(),
            BaseQuery::HAVING       => array(),
            BaseQuery::ORDER        => array(
                array('id_objet', BaseQuery::DESC),
            ),
            BaseQuery::LIMIT        => null,
        ));
    }

    public function testLimit()
    {
        $query = BaseQuery::create()->limit(100, 20);
        $this->assertEquals($query->getParts(), array(
            BaseQuery::COLUMNS      => array(),
            BaseQuery::FROM         => null,
            BaseQuery::INNER_JOIN   => array(),
            BaseQuery::LEFT_JOIN    => array(),
            BaseQuery::WHERE        => null,
            BaseQuery::GROUP        => array(),
            BaseQuery::HAVING       => array(),
            BaseQuery::ORDER        => array(),
            BaseQuery::LIMIT        => array(100, 20),
        ));
    }

    public function testReset()
    {
        $query = BaseQuery::create()->from('orphea.objets', array('id_objet', 'titre_court'));
        $query->reset(BaseQuery::FROM);
        $this->assertEquals($query->getParts(), array(
            BaseQuery::COLUMNS      => array(),
            BaseQuery::FROM         => null,
            BaseQuery::INNER_JOIN   => array(),
            BaseQuery::LEFT_JOIN    => array(),
            BaseQuery::WHERE        => null,
            BaseQuery::GROUP        => array(),
            BaseQuery::HAVING       => array(),
            BaseQuery::ORDER        => array(),
            BaseQuery::LIMIT        => null,
        ));
    }

    public function testResetColumns()
    {
        $query = BaseQuery::create()
            ->from('orphea.objets', array('id_objet', 'titre_court'))
            ->joinInner('orphea.iptc', 'id=id_obj', array('datetime_created'));
        $query->reset(BaseQuery::COLUMNS);

        $this->assertEquals($query->getParts(), array(
            BaseQuery::COLUMNS      => array(),
            BaseQuery::FROM         => null,
            BaseQuery::FROM         => array(
                'tableName'  => 'orphea.objets',
                'schemaName' => null,
                'cols' => null,
            ),
            BaseQuery::INNER_JOIN   => array(
                'orphea.iptc' => array(
                    'tableName'  => 'orphea.iptc',
                    'schemaName' => null,
                    'cond' => 'id=id_obj',
                    'cols' => null,
                ),
            ),
            BaseQuery::LEFT_JOIN    => array(),
            BaseQuery::WHERE        => null,
            BaseQuery::GROUP        => array(),
            BaseQuery::HAVING       => array(),
            BaseQuery::ORDER        => array(),
            BaseQuery::LIMIT        => null,
        ));
    }
}
