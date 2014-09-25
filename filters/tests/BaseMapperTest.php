<?php
require_once LIBRARY_PATH.'/phpunit/ControllerTest.php';
require_once APPLICATION_PATH."/common/models/Filter/BaseMapper.php";
 
class BaseMapperTest extends Zend_Test_PHPUnit_ControllerTestCase
{
    // setUp выполняется перед каждым тестом
    protected function setUp()
    {
        parent::setUp();
    }

    public function testEmpty()
    {
        $query = BaseQuery::create();
        $mapper = TestMapper::create($query);
        $this->assertTrue($mapper instanceof BaseMapper);
        $this->assertEquals($mapper->getQuery(), $query);
    }

    public function testRenderMethods()
    {
        $mapper = TestMapper::create(BaseQuery::create());

        $this->assertFalse($mapper->isColumnsRendered);
        $this->assertFalse($mapper->isWhereRendered);
        $this->assertFalse($mapper->isCompiled);

        $this->assertEquals($mapper->render(), 'result');

        $this->assertTrue($mapper->isColumnsRendered);
        $this->assertTrue($mapper->isWhereRendered);
        $this->assertTrue($mapper->isCompiled);
    }

    public function testMapTables()
    {
        $mapper = TestMapper::create(BaseQuery::create());

        foreach ($mapper->mapTables() as $alias => $name)
        {
            $this->assertEquals($mapper->mappingTables($alias), $name, $alias . ' != ' . $name);
        }
    }
}

class TestMapper extends BaseMapper
{
    public $isColumnsRendered = false;
    public $isWhereRendered   = false;
    public $isCompiled        = false;

    public function mapTables() // array(OrpheaQuery::TABLE_* => 'SCHEMA.TABLE',)
    {
        return array(
            OrpheaQuery::TABLE_OBJ  => 'VR.OBJETS_PUBLIC',
            OrpheaQuery::TABLE_IPTC => 'VR.OBJETS_PUBLIC',
            OrpheaQuery::TABLE_DESC => 'VR.OBJETS_PUBLIC',
            OrpheaQuery::TABLE_IC   => 'ORPHEA.IPTC_CATEGORIES',
            OrpheaQuery::TABLE_IK   => 'ORPHEA.IPTC_KEYWORDS',
            OrpheaQuery::TABLE_TRANSLATE => 'VR.TRANSLATE_ASSET',
        );
    }

    protected function getRenderedParts() // array(part => method,)
    {
        return array(
            BaseQuery::COLUMNS       => '_renderColumns',
            BaseQuery::WHERE         => '_renderWhere',
        );
    }

    public function _renderColumns()
    {
        $this->isColumnsRendered = true;
    }

    public function _renderWhere()
    {
        $this->isWhereRendered = true;
    }

    protected function compileParts()
    {
        $this->isCompiled = true;
        return 'result';
    }

    public function mappingTables($table)
    {
        return parent::mappingTables($table);
    }
}
