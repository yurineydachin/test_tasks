<?php
require_once LIBRARY_PATH.'/phpunit/ControllerTest.php';
require_once APPLICATION_PATH."/common/models/Filter/SolrMapper.php";
require_once APPLICATION_PATH."/configs/orpheaLibrariesDevelopment.php";
 
class SolrAdapterTest extends Zend_Test_PHPUnit_ControllerTestCase
{
    /**
     * @var SolrAdapter
     */
    protected $_adapter;

    // setUp выполняется перед каждым тестом
    protected function setUp()
    {
        parent::setUp();

        $this->_adapter = Zend_Registry::get('SolrAdapter');
    }

    public function testCheckProperties()
    {
        $this->assertTrue($this->_adapter instanceof SolrAdapter);
    }

    public function testSimpleQuery()
    {
        $query = OrpheaQuery::create()->fromObjets(array('id_objet'));

        $this->assertEquals(count($this->_adapter->fetchAll($query)), SolrMapper::ROWS);
    }

    public function testLimit()
    {
        $query = OrpheaQuery::create()->fromObjets('id_objet')->limit(3);

        $this->assertEquals(count($this->_adapter->fetchAll($query)), 3);
    }

    public function testfetchRow()
    {
        $query = OrpheaQuery::create()->fromObjets(array('id_objet'))->limit(10);

        $this->assertEquals(count($this->_adapter->fetchRow($query)), 1);
    }

    public function testColumns()
    {
        $query = OrpheaQuery::create()->fromObjets(array('ID_OBJET','datetime_created','tiTLE'=>'TITRE_court'))->limit(1);

        $this->assertEquals(array_keys($this->_adapter->fetchRow($query)), array('id_objet','datetime_created','title'));
    }

    public function testWhereIntLess()
    {
        $query = OrpheaQuery::create()->fromObjets('id_objet')->where(IntCond::create(OrpheaQuery::F_OBJ_ID_OBJET, Cond::OP_LESS, 10));

        foreach ($this->_adapter->fetchAll($query) as $row) {
            $this->assertTrue($row['id_objet'] < 10, $row['id_objet'] . ' < 10');
        }
    }

    public function testOrderDesc()
    {
        $max = 100;
        $query = OrpheaQuery::create()->fromObjets('id_objet')->where(IntCond::create(OrpheaQuery::F_OBJ_ID_OBJET, Cond::OP_LESS, $max))->order('id_objet', BaseQuery::DESC);

        foreach ($this->_adapter->fetchAll($query) as $row)
        {
            $this->assertTrue($row['id_objet'] < $max, $row['id_objet'] . ' < ' . $max);
            $max = $row['id_objet'];
        }
    }

    public function testfetchCol()
    {
        $query = OrpheaQuery::create()->fromObjets(array('id_objet'))->limit(5)->order('id_objet', BaseQuery::ASC);

        $this->assertEquals($this->_adapter->fetchCol($query), array(3,4,5,6,9));
    }

    public function testfetchOne()
    {
        $query = OrpheaQuery::create()->fromObjets(array('id_objet'))->limit(5)->order('id_objet', BaseQuery::ASC);

        $this->assertEquals($this->_adapter->fetchOne($query), 3);
    }

    public function testWhereDate()
    {
        $date1 = '2010-01-01 00:00:00';
        $date2 = '2010-02-01 00:00:00';
        $query = OrpheaQuery::create()->fromObjets(array('id'=>'id_objet','date'=>'date_objet'))
            ->where(DateCond::create(OrpheaQuery::F_OBJ_DATE_OBJET, Cond::OP_LESS, $date2))
            ->where(DateCond::create(OrpheaQuery::F_OBJ_DATE_OBJET, Cond::OP_MORE, $date1))
            ->limit(5);

        $rows = $this->_adapter->fetchAll($query);
        $this->assertEquals(count($rows), 5);
        foreach ($rows as $row)
        {
            $this->assertTrue($row['date'] < $date2, $row['date'] . ' < ' . $date2);
            $this->assertTrue($row['date'] > $date1, $row['date'] . ' < ' . $date1);
        }
    }

    public function testCount()
    {
        $query = OrpheaQuery::create()->fromObjets('id_objet')->where(IntCond::create(OrpheaQuery::F_OBJ_ID_OBJET, Cond::OP_LESS, 10))->limit(10);
        $cnt = count($this->_adapter->fetchCol($query));

        $queryCount = OrpheaQuery::create()->fromObjets('count(id_objet)')->where(IntCond::create(OrpheaQuery::F_OBJ_ID_OBJET, Cond::OP_LESS, 10));

        $this->assertEquals($this->_adapter->fetchOne($queryCount), $cnt); // count from db
        $this->assertEquals($this->_adapter->getLastCount(), $cnt); // count from db
    }
}
