<?php
require_once LIBRARY_PATH.'/phpunit/ControllerTest.php';
require_once APPLICATION_PATH."/common/models/Filter/VrMapper.php";
require_once APPLICATION_PATH."/configs/orpheaLibrariesDevelopment.php";
 
class VrMapperTest extends Zend_Test_PHPUnit_ControllerTestCase
{
    public function testEmpty()
    {
        $query = BaseQuery::create();
        $mapper = VrMapper::create($query);
        $this->assertTrue($mapper instanceof VrMapper);
        $this->assertEquals($mapper->getQuery(), $query);
    }

    public function testFrom()
    {
        $query = OrpheaQuery::create()->fromObjets(array(OrpheaQuery::F_OBJ_ID_OBJET, OrpheaQuery::F_OBJ_ID_STOCK));
        $mapper = VrMapper::create($query);

        $this->assertEquals($mapper->render(), 'SELECT "OBJETS_PUBLIC"."ID_OBJET", "OBJETS_PUBLIC"."ID_STOCK" FROM "VR"."OBJETS_PUBLIC"');
    }

    public function testJoinTranslate()
    {
        $query = OrpheaQuery::create()->fromObjets('ID_OBJET')->joinTranslate();
        $mapper = VrMapper::create($query);

        $this->assertEquals($mapper->render(), 
            'SELECT "OBJETS_PUBLIC"."ID_OBJET" FROM "VR"."OBJETS_PUBLIC"' . "\n"
            . ' INNER JOIN "VR"."TRANSLATE_ASSET" ON OBJETS_PUBLIC.ID_OBJET = TRANSLATE_ASSET.ASSET_ID'
        );
    }

    public function testInnerJoin()
    {
        $query = OrpheaQuery::create()->fromObjets('ID_OBJET')->joinInner('TRANSLATE_ASSET', 'TRANSLATE_ASSET.ASSET_ID = ' . OrpheaQuery::F_OBJ_ID_OBJET, null, 'VR');
        $mapper = VrMapper::create($query);

        $this->assertEquals($mapper->render(), 
            'SELECT "OBJETS_PUBLIC"."ID_OBJET" FROM "VR"."OBJETS_PUBLIC"' . "\n"
            . ' INNER JOIN "VR"."TRANSLATE_ASSET" ON TRANSLATE_ASSET.ASSET_ID = OBJETS_PUBLIC.ID_OBJET'
        );
    }

    public function testLeftJoin()
    {
        $query = OrpheaQuery::create()->fromObjets('ID_OBJET')->joinLeft('TRANSLATE_ASSET', 'TRANSLATE_ASSET.ASSET_ID = ' . OrpheaQuery::F_OBJ_ID_OBJET, null, 'VR');
        $mapper = VrMapper::create($query);

        $this->assertEquals($mapper->render(), 
            'SELECT "OBJETS_PUBLIC"."ID_OBJET" FROM "VR"."OBJETS_PUBLIC"' . "\n"
            . ' LEFT JOIN "VR"."TRANSLATE_ASSET" ON TRANSLATE_ASSET.ASSET_ID = OBJETS_PUBLIC.ID_OBJET'
        );
    }

    public function testWhereInt()
    {
        $query = OrpheaQuery::create()->fromObjets(array(OrpheaQuery::F_OBJ_ID_OBJET, OrpheaQuery::F_OBJ_ID_STOCK))->library(12);
        $mapper = VrMapper::create($query);

        $this->assertEquals($mapper->render(), 'SELECT "OBJETS_PUBLIC"."ID_OBJET", "OBJETS_PUBLIC"."ID_STOCK" FROM "VR"."OBJETS_PUBLIC"'
            . ' WHERE (OBJETS_PUBLIC.ID_STOCK = 12)');
    }

    public function testWhereStr()
    {
        $query = OrpheaQuery::create()->fromObjets(array(OrpheaQuery::F_OBJ_ID_OBJET, OrpheaQuery::F_OBJ_ID_STOCK))->asset_title('Заголовок фото%', Cond::OP_LIKE);
        $mapper = VrMapper::create($query);

        $this->assertEquals($mapper->render(), 'SELECT "OBJETS_PUBLIC"."ID_OBJET", "OBJETS_PUBLIC"."ID_STOCK" FROM "VR"."OBJETS_PUBLIC"'
            . ' WHERE (LOWER(OBJETS_PUBLIC.TITRE_COURT) LIKE \'%заголовок фото%\')');
    }

    public function testWhereStrBoth()
    {
        $query = OrpheaQuery::create()->fromObjets(array(OrpheaQuery::F_OBJ_ID_OBJET, OrpheaQuery::F_OBJ_ID_STOCK))->asset_title('Заголовок фото');
        $mapper = VrMapper::create($query);

        $this->assertEquals($mapper->render(), 'SELECT "OBJETS_PUBLIC"."ID_OBJET", "OBJETS_PUBLIC"."ID_STOCK" FROM "VR"."OBJETS_PUBLIC"'
            . ' WHERE (LOWER(OBJETS_PUBLIC.TITRE_COURT) LIKE \'%заголовок фото%\')');
    }

    public function testWhereDate()
    {
        $query = OrpheaQuery::create()->fromObjets(array(OrpheaQuery::F_OBJ_ID_OBJET, OrpheaQuery::F_OBJ_ID_STOCK))->dateObjet('2013-06-01 15:30:55', Cond::OP_LESS_EQUAL);
        $mapper = VrMapper::create($query);

        $this->assertEquals($mapper->render(), 'SELECT "OBJETS_PUBLIC"."ID_OBJET", "OBJETS_PUBLIC"."ID_STOCK" FROM "VR"."OBJETS_PUBLIC"'
            . ' WHERE (OBJETS_PUBLIC.DATE_OBJET <= TO_DATE(\'2013-06-01 15:30:55\', \'YYYY-MM-DD HH24:MI:SS\'))');
    }

    public function testWhereIgnorePublic()
    {
        $query = OrpheaQuery::create()->fromObjets(OrpheaQuery::F_OBJ_ID_OBJET)
            ->where(IntCond::create(OrpheaQuery::F_OBJ_PUBLIC, Cond::OP_MORE, 0));
        $mapper = VrMapper::create($query);

        $this->assertEquals($mapper->render(), 'SELECT "OBJETS_PUBLIC"."ID_OBJET" FROM "VR"."OBJETS_PUBLIC"');
    }

    public function testWhereIgnoreNotInStock()
    {
        $query = OrpheaQuery::create()->fromObjets(OrpheaQuery::F_OBJ_ID_OBJET)
            ->where(IntCond::create(OrpheaQuery::F_OBJ_ID_STOCK, Cond::OP_NOT_IN, array(14, 0)))
            ->where(IntCond::create(OrpheaQuery::F_OBJ_ID_OBJET, Cond::OP_LESS, 10));
        $mapper = VrMapper::create($query);

        $this->assertEquals($mapper->render(), 'SELECT "OBJETS_PUBLIC"."ID_OBJET" FROM "VR"."OBJETS_PUBLIC" WHERE (OBJETS_PUBLIC.ID_OBJET < 10)');
    }

    public function testWhereIgnoreDatePub()
    {
        $query = OrpheaQuery::create()->fromObjets(OrpheaQuery::F_OBJ_ID_OBJET)
            ->where(DateCond::create(OrpheaQuery::F_OBJ_PUBBEGINDATE, Cond::OP_LESS, '2013-06-01 00:00:00'))
            ->where(DateCond::create(OrpheaQuery::F_OBJ_PUBENDDATE, Cond::OP_MORE, '2013-07-01 00:00:00'))
            ->where(IntCond::create(OrpheaQuery::F_OBJ_ID_OBJET, Cond::OP_LESS, 10));
        $mapper = VrMapper::create($query);

        $this->assertEquals($mapper->render(), 'SELECT "OBJETS_PUBLIC"."ID_OBJET" FROM "VR"."OBJETS_PUBLIC" WHERE (OBJETS_PUBLIC.ID_OBJET < 10)');
    }

    public function testWhereIgnoreJoinCond()
    {
        $query = OrpheaQuery::create()->fromObjets(OrpheaQuery::F_OBJ_ID_OBJET)->constraintable();
        $mapper = VrMapper::create($query);

        $this->assertEquals($mapper->render(), 'SELECT "OBJETS_PUBLIC"."ID_OBJET" FROM "VR"."OBJETS_PUBLIC"');
    }

    public function testIgnoreFrom()
    {
        $query = OrpheaQuery::create()->from(OrpheaQuery::TABLE_IPTC, 'ID_OBJET');
        $mapper = VrMapper::create($query);

        $this->assertEquals($mapper->render(), 'SELECT "OBJETS_PUBLIC"."ID_OBJET" FROM "VR"."OBJETS_PUBLIC"');
    }

    public function testIgnoreInnerJoin()
    {
        $query = OrpheaQuery::create()->fromObjets('ID_OBJET')->joinIptc();
        $mapper = VrMapper::create($query);

        $this->assertEquals($mapper->render(), 'SELECT "OBJETS_PUBLIC"."ID_OBJET" FROM "VR"."OBJETS_PUBLIC"');
    }

    public function testIgnoreLeftJoin()
    {
        $query = OrpheaQuery::create()->fromObjets('ID_OBJET')->joinDescription(OrpheaQuery::F_DESC_ARCHIVE, true);
        $mapper = VrMapper::create($query);

        $this->assertEquals($mapper->render(), 'SELECT "OBJETS_PUBLIC"."ID_OBJET", "OBJETS_PUBLIC"."SHORTSTRING1" FROM "VR"."OBJETS_PUBLIC"');
    }

    public function testOrderDefault()
    {
        $query = OrpheaQuery::create()->fromObjets('ID_OBJET')->orderBy();
        $mapper = VrMapper::create($query);

        $this->assertEquals($mapper->render(), 'SELECT "OBJETS_PUBLIC"."ID_OBJET" FROM "VR"."OBJETS_PUBLIC" ORDER BY "OBJETS_PUBLIC"."DATETIME_CREATED" DESC, "OBJETS_PUBLIC"."ID_OBJET" DESC');
    }

    public function testJoinCategories()
    {
        $query = OrpheaQuery::create()->fromObjets('ID_OBJET')->category(array(1,2,3));
        $mapper = VrMapper::create($query);

        $this->assertEquals($mapper->render(), 
            'SELECT "OBJETS_PUBLIC"."ID_OBJET" FROM "VR"."OBJETS_PUBLIC"' . "\n"
            . ' INNER JOIN "ORPHEA"."IPTC_CATEGORIES" ON OBJETS_PUBLIC.ID_OBJET = IPTC_CATEGORIES.ID_OBJET'
            . ' WHERE (IPTC_CATEGORIES.ID_ALL_CATEGORIES IN (1, 2, 3))'
        );
    }

    public function testJoinKeywords()
    {
        $query = OrpheaQuery::create()->fromObjets('ID_OBJET')->keyword(array(1,2,3));
        $mapper = VrMapper::create($query);

        $this->assertEquals($mapper->render(), 
            'SELECT "OBJETS_PUBLIC"."ID_OBJET" FROM "VR"."OBJETS_PUBLIC"' . "\n"
            . ' INNER JOIN "ORPHEA"."IPTC_KEYWORDS" ON OBJETS_PUBLIC.ID_OBJET = IPTC_KEYWORDS.ID_OBJET'
            . ' WHERE (IPTC_KEYWORDS.ID_ALL_KEYWORD IN (1, 2, 3))'
        );
    }

    public function testLightboxContent()
    {
        $query = OrpheaQuery::create()->fromObjets('ID_OBJET')->lightbox(array(1,2,3));
        $mapper = VrMapper::create($query);

        $this->assertEquals($mapper->render(), 
            'SELECT "OBJETS_PUBLIC"."ID_OBJET" FROM "VR"."OBJETS_PUBLIC"' . "\n"
            . ' INNER JOIN "ORPHEA"."CONTAINS_OBJECTS" ON OBJETS_PUBLIC.ID_OBJET = CONTAINS_OBJECTS.ID_OBJET'
            . ' WHERE (CONTAINS_OBJECTS.ID_CHUTIER IN (1, 2, 3))'
        );
    }

    public function testOrientation()
    {
        $query = OrpheaQuery::create()->fromObjets('ID_OBJET')->orientation('kvadrat');
        $mapper = VrMapper::create($query);

        $this->assertEquals($mapper->render(), 
            'SELECT "OBJETS_PUBLIC"."ID_OBJET" FROM "VR"."OBJETS_PUBLIC"' .
            ' WHERE ((OBJETS_PUBLIC.VERTICAL_PERCENT <= ' . OrpheaQuery::ORIENTATION_TOP . ') AND (OBJETS_PUBLIC.VERTICAL_PERCENT >= ' . OrpheaQuery::ORIENTATION_BOTTOM . '))'
        );
    }
}
