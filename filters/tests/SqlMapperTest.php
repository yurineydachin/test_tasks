<?php
require_once LIBRARY_PATH.'/phpunit/ControllerTest.php';
require_once APPLICATION_PATH."/common/models/Filter/SqlMapper.php";
 
class SqlMapperTest extends Zend_Test_PHPUnit_ControllerTestCase
{
    public function testEmpty()
    {
        $query = BaseQuery::create();
        $mapper = TestSqlMapper::create($query);
        $this->assertTrue($mapper instanceof TestSqlMapper);
        $this->assertEquals($mapper->getQuery(), $query);
    }

    public function testFrom()
    {
        $query = BaseQuery::create()->from('OBJETS', 'ID_OBJET', 'ORPHEA');
        $mapper = TestSqlMapper::create($query);

        $this->assertEquals($mapper->render(), 'SELECT "OBJETS"."ID_OBJET" FROM "ORPHEA"."OBJETS"');
    }

    public function testInnerJoin()
    {
        $query = BaseQuery::create()->from('OBJETS', 'ID_OBJET', 'ORPHEA')->joinInner('TRANSLATE_ASSET', 'TRANSLATE_ASSET.ASSET_ID = ORPHEA.ID_OBJET', null, 'VR');
        $mapper = TestSqlMapper::create($query);

        $this->assertEquals($mapper->render(), 
            'SELECT "OBJETS"."ID_OBJET" FROM "ORPHEA"."OBJETS"' . "\n"
            . ' INNER JOIN "VR"."TRANSLATE_ASSET" ON TRANSLATE_ASSET.ASSET_ID = ORPHEA.ID_OBJET'
        );
    }

    public function testLeftJoin()
    {
        $query = BaseQuery::create()->from('OBJETS', 'ID_OBJET', 'ORPHEA')->joinLeft('TRANSLATE_ASSET', 'TRANSLATE_ASSET.ASSET_ID = ORPHEA.ID_OBJET', null, 'VR');
        $mapper = TestSqlMapper::create($query);

        $this->assertEquals($mapper->render(), 
            'SELECT "OBJETS"."ID_OBJET" FROM "ORPHEA"."OBJETS"' . "\n"
            . ' LEFT JOIN "VR"."TRANSLATE_ASSET" ON TRANSLATE_ASSET.ASSET_ID = ORPHEA.ID_OBJET'
        );
    }

    public function testWhereInt()
    {
        $query = BaseQuery::create()->from('OBJETS', 'ID_OBJET', 'ORPHEA')->where(IntCond::create('ID_OBJET', Cond::OP_EQUAL, 1234));
        $mapper = TestSqlMapper::create($query);

        $this->assertEquals($mapper->render(), 'SELECT "OBJETS"."ID_OBJET" FROM "ORPHEA"."OBJETS"'
            . ' WHERE (ID_OBJET = 1234)');
    }

    public function testWhereInt0()
    {
        $query = BaseQuery::create()->from('OBJETS', 'ID_OBJET', 'ORPHEA')->where(IntCond::create('ID_OBJET', Cond::OP_EQUAL, 0));
        $mapper = TestSqlMapper::create($query);

        $this->assertEquals($mapper->render(), 'SELECT "OBJETS"."ID_OBJET" FROM "ORPHEA"."OBJETS"'
            . ' WHERE (ID_OBJET = 0)');
    }

    public function testWhereIntNvl()
    {
        $query = BaseQuery::create()->from('OBJETS', 'ID_OBJET', 'ORPHEA')->where(IntCond::create('ID_OBJET', Cond::OP_EQUAL, 1234)->addOption(Cond::OPTION_NVL, 0));
        $mapper = TestSqlMapper::create($query);

        $this->assertEquals($mapper->render(), 'SELECT "OBJETS"."ID_OBJET" FROM "ORPHEA"."OBJETS"'
            . ' WHERE (NVL(ID_OBJET, 0) = 1234)');
    }

    public function testWhereArray()
    {
        $query = BaseQuery::create()->from('OBJETS', 'ID_OBJET', 'ORPHEA')->where(IntCond::create('ID_OBJET', Cond::OP_EQUAL, array(1,2,3)));
        $mapper = TestSqlMapper::create($query);

        $this->assertEquals($mapper->render(), 'SELECT "OBJETS"."ID_OBJET" FROM "ORPHEA"."OBJETS"'
            . ' WHERE (ID_OBJET IN (1, 2, 3))');
    }

    public function testWhereStr()
    {
        $query = BaseQuery::create()->from('OBJETS', 'ID_OBJET', 'ORPHEA')->where(StrCond::create('TITRE_COURT', Cond::OP_EQUAL, 'Заголовок фото'));
        $mapper = TestSqlMapper::create($query);

        $this->assertEquals($mapper->render(), 'SELECT "OBJETS"."ID_OBJET" FROM "ORPHEA"."OBJETS"'
            . ' WHERE (TITRE_COURT = \'Заголовок фото\')');
    }

    public function testWhereIntToStr()
    {
        $query = BaseQuery::create()->from('OBJETS', 'ID_OBJET', 'ORPHEA')->where(StrCond::create('TITRE_COURT', Cond::OP_EQUAL, 1234));
        $mapper = TestSqlMapper::create($query);

        $this->assertEquals($mapper->render(), 'SELECT "OBJETS"."ID_OBJET" FROM "ORPHEA"."OBJETS"'
            . ' WHERE (TITRE_COURT = \'1234\')');
    }

    public function testWhereStrLowerNvl()
    {
        $query = BaseQuery::create()->from('OBJETS', 'ID_OBJET', 'ORPHEA')->where(StrCond::create('TITRE_COURT', Cond::OP_EQUAL, 'Заголовок фото')->addOption(Cond::OPTION_LOWER)->addOption(Cond::OPTION_NVL, 'заголовок'));
        $mapper = TestSqlMapper::create($query);

        $this->assertEquals($mapper->render(), 'SELECT "OBJETS"."ID_OBJET" FROM "ORPHEA"."OBJETS"'
            . ' WHERE (NVL(LOWER(TITRE_COURT), \'заголовок\') = \'заголовок фото\')');
    }

    public function testWhereStrFullText()
    {
        $query = BaseQuery::create()->from('OBJETS', 'ID_OBJET', 'ORPHEA')->where(StrCond::create('TITRE_COURT', Cond::OP_EQUAL, 'Заголовок фото')->addOption(Cond::OPTION_FULL_TEXT));
        $mapper = TestSqlMapper::create($query);

        $this->assertEquals($mapper->render(), 'SELECT "OBJETS"."ID_OBJET" FROM "ORPHEA"."OBJETS"'
            . ' WHERE (CONTAINS(TITRE_COURT, \'(Заголовок) AND (фото)\') > 0)');
    }

    public function testWhereStrFullTextStar()
    {
        $query = BaseQuery::create()->from('OBJETS', 'ID_OBJET', 'ORPHEA')->where(StrCond::create('TITRE_COURT', Cond::OP_EQUAL, 'Заголов* фото')->addOption(Cond::OPTION_FULL_TEXT));
        $mapper = TestSqlMapper::create($query);

        $this->assertEquals($mapper->render(), 'SELECT "OBJETS"."ID_OBJET" FROM "ORPHEA"."OBJETS"'
            . ' WHERE (CONTAINS(TITRE_COURT, \'(Заголов%) AND (фото)\') > 0)');
    }

    public function testWhereStrFullTextOr()
    {
        $query = BaseQuery::create()->from('OBJETS', 'ID_OBJET', 'ORPHEA')->where(StrCond::create('TITRE_COURT', Cond::OP_EQUAL, 'Заголов* фото или описание')->addOption(Cond::OPTION_FULL_TEXT));
        $mapper = TestSqlMapper::create($query);

        $this->assertEquals($mapper->render(), 'SELECT "OBJETS"."ID_OBJET" FROM "ORPHEA"."OBJETS"'
            . ' WHERE (CONTAINS(TITRE_COURT, \'((Заголов%) AND (фото)) OR ((описание))\') > 0)');
    }

    public function testWhereStrFullTextDeleteAnd()
    {
        $query = BaseQuery::create()->from('OBJETS', 'ID_OBJET', 'ORPHEA')->where(StrCond::create('TITRE_COURT', Cond::OP_EQUAL, 'man and woman or and or ')->addOption(Cond::OPTION_FULL_TEXT));
        $mapper = TestSqlMapper::create($query);

        $this->assertEquals($mapper->render(), 'SELECT "OBJETS"."ID_OBJET" FROM "ORPHEA"."OBJETS"'
            . ' WHERE (CONTAINS(TITRE_COURT, \'((man) AND (woman))\') > 0)');
    }

    public function testWhereDate()
    {
        $query = BaseQuery::create()->from('OBJETS', 'ID_OBJET', 'ORPHEA')->where(DateCond::create('DATE_OBJET', Cond::OP_MORE, '2013-06-01 15:30:55'));
        $mapper = TestSqlMapper::create($query);

        $this->assertEquals($mapper->render(), 'SELECT "OBJETS"."ID_OBJET" FROM "ORPHEA"."OBJETS"'
            . ' WHERE (DATE_OBJET > TO_DATE(\'2013-06-01 15:30:55\', \'YYYY-MM-DD HH24:MI:SS\'))');
    }

    public function testWhereDateNvl()
    {
        $query = BaseQuery::create()->from('OBJETS', 'ID_OBJET', 'ORPHEA')->where(DateCond::create('DATE_OBJET', Cond::OP_MORE, '2013-06-01 15:30:55')->addOption(Cond::OPTION_NVL, '2013-01-01 00:00:00'));
        $mapper = TestSqlMapper::create($query);

        $this->assertEquals($mapper->render(), 'SELECT "OBJETS"."ID_OBJET" FROM "ORPHEA"."OBJETS"'
            . ' WHERE (NVL(DATE_OBJET, TO_DATE(\'2013-01-01 00:00:00\', \'YYYY-MM-DD HH24:MI:SS\')) > TO_DATE(\'2013-06-01 15:30:55\', \'YYYY-MM-DD HH24:MI:SS\'))');
    }

    public function testWhereBool()
    {
        $query = BaseQuery::create()->from('OBJETS', 'ID_OBJET', 'ORPHEA')->where(BoolCond::create('IS_PRIVATE_URL', Cond::OP_EQUAL, true));
        $mapper = TestSqlMapper::create($query);

        $this->assertEquals($mapper->render(), 'SELECT "OBJETS"."ID_OBJET" FROM "ORPHEA"."OBJETS"'
            . ' WHERE (IS_PRIVATE_URL = 1)');
    }

    public function testWhereJoin()
    {
        $query = BaseQuery::create()->from('OBJETS', 'ID_OBJET', 'ORPHEA')
            ->where(BoolCond::create('IS_PRIVATE_URL', Cond::OP_EQUAL, true))
            ->where(JoinCond::create(null, Cond::OP_OR)
                ->add(IntCond::create('ID_OBJET', Cond::OP_EQUAL, 1234))
                ->add(DateCond::create('DATE_OBJET', Cond::OP_MORE, '2013-06-01 15:30:55'))
                ->add(StrCond::create('TITRE_COURT', Cond::OP_EQUAL, 'Заголовок фото'))
            );
        $mapper = TestSqlMapper::create($query);

        $this->assertEquals($mapper->render(), 'SELECT "OBJETS"."ID_OBJET" FROM "ORPHEA"."OBJETS"'
            . ' WHERE ((IS_PRIVATE_URL = 1) AND ((ID_OBJET = 1234) OR (DATE_OBJET > TO_DATE(\'2013-06-01 15:30:55\', \'YYYY-MM-DD HH24:MI:SS\')) OR (TITRE_COURT = \'Заголовок фото\')))');
    }

    public function testGroup()
    {
        $query = BaseQuery::create()->from('OBJETS', 'ID_OBJET', 'ORPHEA')->group(array('ID_STOCK', 'ID_LIASSE'));
        $mapper = TestSqlMapper::create($query);

        $this->assertEquals($mapper->render(), 'SELECT "OBJETS"."ID_OBJET" FROM "ORPHEA"."OBJETS" GROUP BY "ID_STOCK",' . "\n\t" . '"ID_LIASSE"');
    }

    public function testHaving()
    {
        $query = BaseQuery::create()->from('OBJETS', 'ID_OBJET', 'ORPHEA')->group(array('ID_STOCK'))->having('COUNT(ID_OBJET) > 10');
        $mapper = TestSqlMapper::create($query);

        $this->assertEquals($mapper->render(), 'SELECT "OBJETS"."ID_OBJET" FROM "ORPHEA"."OBJETS" GROUP BY "ID_STOCK" HAVING (COUNT(ID_OBJET) > 10)');
    }

    public function testOrder()
    {
        $query = BaseQuery::create()->from('OBJETS', 'ID_OBJET', 'ORPHEA')->order('DATETIME_CREATED', BaseQuery::DESC)->order('ID_OBJET', BaseQuery::DESC);
        $mapper = TestSqlMapper::create($query);

        $this->assertEquals($mapper->render(), 'SELECT "OBJETS"."ID_OBJET" FROM "ORPHEA"."OBJETS" ORDER BY "DATETIME_CREATED" DESC, "ID_OBJET" DESC');
    }

    // fake: only for wrong Zend_Db_Adapter_Oracle
    public function testLimit()
    {
        $query = BaseQuery::create()->from('OBJETS', 'ID_OBJET', 'ORPHEA')->limit(10, 20);
        $mapper = TestSqlMapper::create($query);

        $limit_sql = 'SELECT z2.*
            FROM (
                SELECT z1.*, ROWNUM AS "zend_db_rownum"
                FROM (
                    SELECT "OBJETS"."ID_OBJET" FROM "ORPHEA"."OBJETS"
                ) z1
            ) z2
            WHERE z2."zend_db_rownum" BETWEEN 21 AND 30';
        $this->assertEquals($mapper->render(), $limit_sql);
    }
}

class TestSqlMapper extends SqlMapper
{
    protected function mapTables() // array(OrpheaQuery::TABLE_* => 'SCHEMA.TABLE',)
    {
        return array();
    }

    protected function mapFields() // array(OrpheaQuery::F_* => 'TABLE.FIELD',)
    {
        return array();
    }
}
