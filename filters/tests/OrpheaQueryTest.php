<?php
require_once LIBRARY_PATH.'/phpunit/ControllerTest.php';
require_once APPLICATION_PATH."/common/models/Filter/OrpheaQuery.php";
require_once APPLICATION_PATH."/configs/orpheaLibrariesDevelopment.php";
 
class OrpheaQueryTest extends Zend_Test_PHPUnit_ControllerTestCase
{
    // setUp выполняется перед каждым тестом
    protected function setUp()
    {
        parent::setUp();
    }

    public function testEmpty()
    {
        $query = OrpheaQuery::create();
        $this->assertTrue($query instanceof OrpheaQuery);
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
        $query = OrpheaQuery::create()->fromObjets(array('id_objet', 'titre_court'));
        $this->assertEquals($query->getParts(), array(
            BaseQuery::COLUMNS      => array(),
            BaseQuery::FROM         => array(
                'tableName'  => OrpheaQuery::TABLE_OBJ,
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

    public function testJoinIptc()
    {
        $query = OrpheaQuery::create()->joinIptc(array('id_objet', 'titre_court'));
        $this->assertEquals($query->getParts(), array(
            BaseQuery::COLUMNS      => array(),
            BaseQuery::FROM         => null,
            BaseQuery::INNER_JOIN   => array(
                '<orphea.iptc>' => array(
                    'tableName'  => OrpheaQuery::TABLE_IPTC,
                    'schemaName' => null,
                    'cond' => OrpheaQuery::F_OBJ_ID_OBJET . ' = ' . OrpheaQuery::F_IPTC_ID_OBJET,
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

    public function testJoinTranslate()
    {
        $query = OrpheaQuery::create()->joinTranslate(array('asset_id', 'headline'));
        $this->assertEquals($query->getParts(), array(
            BaseQuery::COLUMNS      => array(),
            BaseQuery::FROM         => null,
            BaseQuery::INNER_JOIN   => array(
                '<vr.translate_asset>' => array(
                    'tableName'  => OrpheaQuery::TABLE_TRANSLATE,
                    'schemaName' => null,
                    'cond' => OrpheaQuery::F_OBJ_ID_OBJET . ' = ' . OrpheaQuery::F_TRANSLATE_ID_OBJET,
                    'cols' => array('asset_id', 'headline'),
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

    public function testLibrary()
    {
        $query = OrpheaQuery::create()->library(array(1,2));
        
        $cond = $query->getPart(BaseQuery::WHERE);
        $this->assertEquals($cond->getName(),  OrpheaQuery::F_OBJ_ID_STOCK);
        $this->assertEquals($cond->getOp(),    Cond::OP_IN);
        $this->assertEquals($cond->getValue(), array(1,2));
    }

    public function testCountry()
    {
        $query = OrpheaQuery::create()->country('Россия');
        
        $this->assertEquals($query->getPart(BaseQuery::INNER_JOIN), array(
                '<orphea.iptc>' => array(
                    'tableName'  => OrpheaQuery::TABLE_IPTC,
                    'schemaName' => null,
                    'cond' => OrpheaQuery::F_OBJ_ID_OBJET . ' = ' . OrpheaQuery::F_IPTC_ID_OBJET,
                    'cols' => null,
                ),
        ));
        $cond = $query->getPart(BaseQuery::WHERE);
        $this->assertEquals($cond->getName(),  OrpheaQuery::F_IPTC_COUNTRY);
        $this->assertEquals($cond->getOp(),    Cond::OP_EQUAL);
        $this->assertEquals($cond->getValue(), 'Россия');
        $this->assertEquals($cond->getOptions(), array(Cond::OPTION_ATOM_STR => Cond::OPTION_ATOM_STR));
    }

    public function testRegion()
    {
        $query = OrpheaQuery::create()->region('Московская область');
        
        $this->assertEquals($query->getPart(BaseQuery::INNER_JOIN), array(
                '<orphea.iptc>' => array(
                    'tableName'  => OrpheaQuery::TABLE_IPTC,
                    'schemaName' => null,
                    'cond' => OrpheaQuery::F_OBJ_ID_OBJET . ' = ' . OrpheaQuery::F_IPTC_ID_OBJET,
                    'cols' => null,
                ),
        ));
        $cond = $query->getPart(BaseQuery::WHERE);
        $this->assertEquals($cond->getName(),  OrpheaQuery::F_IPTC_REGION);
        $this->assertEquals($cond->getOp(),    Cond::OP_EQUAL);
        $this->assertEquals($cond->getValue(), 'Московская область');
        $this->assertEquals($cond->getOptions(), array(Cond::OPTION_ATOM_STR => Cond::OPTION_ATOM_STR));
    }

    public function testCity()
    {
        $query = OrpheaQuery::create()->city('Москва');
        
        $this->assertEquals($query->getPart(BaseQuery::INNER_JOIN), array(
                '<orphea.iptc>' => array(
                    'tableName'  => OrpheaQuery::TABLE_IPTC,
                    'schemaName' => null,
                    'cond' => OrpheaQuery::F_OBJ_ID_OBJET . ' = ' . OrpheaQuery::F_IPTC_ID_OBJET,
                    'cols' => null,
                ),
        ));
        $cond = $query->getPart(BaseQuery::WHERE);
        $this->assertEquals($cond->getName(),  OrpheaQuery::F_IPTC_CITY);
        $this->assertEquals($cond->getOp(),    Cond::OP_EQUAL);
        $this->assertEquals($cond->getValue(), 'Москва');
        $this->assertEquals($cond->getOptions(), array(Cond::OPTION_ATOM_STR => Cond::OPTION_ATOM_STR));
    }

    public function testLocation()
    {
        $query = OrpheaQuery::create()->country('Россия')
            ->region('Московская область')
            ->city('Москва');

        $this->assertEquals($query->getPart(BaseQuery::INNER_JOIN), array(
                '<orphea.iptc>' => array(
                    'tableName'  => OrpheaQuery::TABLE_IPTC,
                    'schemaName' => null,
                    'cond' => OrpheaQuery::F_OBJ_ID_OBJET . ' = ' . OrpheaQuery::F_IPTC_ID_OBJET,
                    'cols' => null,
                ),
        ));
        $cond = $query->getPart(BaseQuery::WHERE);

        $this->assertTrue(is_null($cond->getName()));
        $this->assertEquals($cond->getOp(), Cond::OP_AND);

        $val = $cond->getValue();
        $this->assertEquals(count($val), 3);
        $this->assertEquals($val[0]->getName(), OrpheaQuery::F_IPTC_COUNTRY);
        $this->assertEquals($val[1]->getName(), OrpheaQuery::F_IPTC_REGION);
        $this->assertEquals($val[2]->getName(), OrpheaQuery::F_IPTC_CITY);
    }

    public function testCategory()
    {
        $query = OrpheaQuery::create()->category(50);
        
        $this->assertEquals($query->getPart(BaseQuery::INNER_JOIN), array(
                '<orphea.iptc_categories>' => array(
                    'tableName'  => OrpheaQuery::TABLE_IC,
                    'schemaName' => null,
                    'cond' => OrpheaQuery::F_OBJ_ID_OBJET . ' = ' . OrpheaQuery::F_IC_ID_OBJET,
                    'cols' => null,
                ),
        ));
        $cond = $query->getPart(BaseQuery::WHERE);
        $this->assertEquals($cond->getName(),  OrpheaQuery::F_IC_ID_ALL_CAT);
        $this->assertEquals($cond->getOp(),    Cond::OP_EQUAL);
        $this->assertEquals($cond->getValue(), 50);
    }

    public function testKeyword()
    {
        $query = OrpheaQuery::create()->keyword(array(30,31,32));
        
        $this->assertEquals($query->getPart(BaseQuery::INNER_JOIN), array(
                '<orphea.iptc_keywords>' => array(
                    'tableName'  => OrpheaQuery::TABLE_IK,
                    'schemaName' => null,
                    'cond' => OrpheaQuery::F_OBJ_ID_OBJET . ' = ' . OrpheaQuery::F_IK_ID_OBJET,
                    'cols' => null,
                ),
        ));
        $cond = $query->getPart(BaseQuery::WHERE);
        $this->assertEquals($cond->getName(),  OrpheaQuery::F_IK_ID_ALL_KEY);
        $this->assertEquals($cond->getOp(),    Cond::OP_IN);
        $this->assertEquals($cond->getValue(), array(30,31,32));
    }

    public function testKeywordEn()
    {
        $query = OrpheaQuery::create()->keyword_en('Word');
        
        $this->assertEquals($query->getPart(BaseQuery::INNER_JOIN), array(
                '<vr.translate_asset>' => array(
                    'tableName'  => OrpheaQuery::TABLE_TRANSLATE,
                    'schemaName' => null,
                    'cond' => OrpheaQuery::F_OBJ_ID_OBJET . ' = ' . OrpheaQuery::F_TRANSLATE_ID_OBJET,
                    'cols' => null,
                ),
        ));
        $cond = $query->getPart(BaseQuery::WHERE);
        $this->assertEquals($cond->getName(),  OrpheaQuery::F_TRANSLATE_KEYWORD);
        $this->assertEquals($cond->getOp(),    Cond::OP_LIKE);
        $this->assertEquals($cond->getValue(), 'word');
        $this->assertEquals($cond->getOptions(), array(Cond::OPTION_LOWER => Cond::OPTION_LOWER, Cond::OPTION_LIKE_BOTH => Cond::OPTION_LIKE_BOTH));
    }

    public function testAssetId()
    {
        $query = OrpheaQuery::create()->asset_id(array(1,2,3));
        
        $cond = $query->getPart(BaseQuery::WHERE);
        $this->assertEquals($cond->getName(),  OrpheaQuery::F_OBJ_ID_OBJET);
        $this->assertEquals($cond->getOp(),    Cond::OP_IN);
        $this->assertEquals($cond->getValue(), array(1,2,3));
    }

    public function testFeatureId()
    {
        $query = OrpheaQuery::create()->feature(3);
        
        $cond = $query->getPart(BaseQuery::WHERE);
        $this->assertEquals($cond->getName(),  OrpheaQuery::F_OBJ_ID_LIASSE);
        $this->assertEquals($cond->getOp(),    Cond::OP_EQUAL);
        $this->assertEquals($cond->getValue(), 3);
    }

    public function testLightboxId()
    {
        $query = OrpheaQuery::create()->lightbox(3);
        
        $this->assertEquals($query->getPart(BaseQuery::INNER_JOIN), array(
                '<orphea.contains_objects>' => array(
                    'tableName'  => OrpheaQuery::TABLE_LIGHTBOX_CONTENT,
                    'schemaName' => null,
                    'cond' => OrpheaQuery::F_OBJ_ID_OBJET . ' = ' . OrpheaQuery::F_LC_ID_OBJET,
                    'cols' => null,
                ),
        ));
        $cond = $query->getPart(BaseQuery::WHERE);
        $this->assertEquals($cond->getName(),  OrpheaQuery::F_LC_ID_LIGHTBOX);
        $this->assertEquals($cond->getOp(),    Cond::OP_EQUAL);
        $this->assertEquals($cond->getValue(), 3);
    }

    public function testSourceIn()
    {
        $query = OrpheaQuery::create()->source(array('Источник1', 'Источник2'));
        
        $this->assertEquals($query->getPart(BaseQuery::INNER_JOIN), array(
                '<orphea.iptc>' => array(
                    'tableName'  => OrpheaQuery::TABLE_IPTC,
                    'schemaName' => null,
                    'cond' => OrpheaQuery::F_OBJ_ID_OBJET . ' = ' . OrpheaQuery::F_IPTC_ID_OBJET,
                    'cols' => null,
                ),
        ));
        $cond = $query->getPart(BaseQuery::WHERE);
        $this->assertEquals($cond->getName(),  OrpheaQuery::F_IPTC_SOURCE);
        $this->assertEquals($cond->getOp(),    Cond::OP_IN);
        $this->assertEquals($cond->getValue(), array('Источник1', 'Источник2'));
    }

    public function testSourceLike()
    {
        $query = OrpheaQuery::create()->source('Источник%', Cond::OP_LIKE);
        
        $this->assertEquals($query->getPart(BaseQuery::INNER_JOIN), array(
                '<orphea.iptc>' => array(
                    'tableName'  => OrpheaQuery::TABLE_IPTC,
                    'schemaName' => null,
                    'cond' => OrpheaQuery::F_OBJ_ID_OBJET . ' = ' . OrpheaQuery::F_IPTC_ID_OBJET,
                    'cols' => null,
                ),
        ));
        $cond = $query->getPart(BaseQuery::WHERE);
        $this->assertEquals($cond->getName(),  OrpheaQuery::F_IPTC_SOURCE);
        $this->assertEquals($cond->getOp(),    Cond::OP_LIKE);
        $this->assertEquals($cond->getValue(), 'источник%');
        $this->assertEquals($cond->getOptions(), array(Cond::OPTION_ATOM_STR => Cond::OPTION_ATOM_STR, Cond::OPTION_LOWER => Cond::OPTION_LOWER, Cond::OPTION_LIKE_BOTH => Cond::OPTION_LIKE_BOTH));
    }

    public function testArchive()
    {
        $query = OrpheaQuery::create()->archive_code('Архив', Cond::OP_LIKE);
        
        $this->assertEquals($query->getPart(BaseQuery::INNER_JOIN), array(
                '<orphea.description>' => array(
                    'tableName'  => OrpheaQuery::TABLE_DESC,
                    'schemaName' => null,
                    'cond' => OrpheaQuery::F_OBJ_ID_OBJET . ' = ' . OrpheaQuery::F_DESC_ID_OBJET,
                    'cols' => null,
                ),
        ));
        $cond = $query->getPart(BaseQuery::WHERE);
        $this->assertEquals($cond->getName(),  OrpheaQuery::F_DESC_ARCHIVE);
        $this->assertEquals($cond->getOp(),    Cond::OP_LIKE);
        $this->assertEquals($cond->getValue(), 'архив');
    }

    public function testAuthor()
    {
        $query = OrpheaQuery::create()->author('Автор');
        
        $this->assertEquals($query->getPart(BaseQuery::INNER_JOIN), array(
                '<orphea.iptc>' => array(
                    'tableName'  => OrpheaQuery::TABLE_IPTC,
                    'schemaName' => null,
                    'cond' => OrpheaQuery::F_OBJ_ID_OBJET . ' = ' . OrpheaQuery::F_IPTC_ID_OBJET,
                    'cols' => null,
                ),
        ));
        $cond = $query->getPart(BaseQuery::WHERE);
        $this->assertEquals($cond->getName(),  OrpheaQuery::F_IPTC_AUTHOR);
        $this->assertEquals($cond->getOp(),    Cond::OP_LIKE);
        $this->assertEquals($cond->getValue(), 'автор');
        $this->assertEquals($cond->getOptions(), array(Cond::OPTION_LOWER => Cond::OPTION_LOWER, Cond::OPTION_LIKE_BOTH => Cond::OPTION_LIKE_BOTH));
    }

    public function testTitle()
    {
        $query = OrpheaQuery::create()->asset_title('Заголовок');
        
        $cond = $query->getPart(BaseQuery::WHERE);
        $this->assertEquals($cond->getName(),  OrpheaQuery::F_OBJ_TITLE);
        $this->assertEquals($cond->getOp(),    Cond::OP_LIKE);
        $this->assertEquals($cond->getValue(), 'заголовок');
        $this->assertEquals($cond->getOptions(), array(Cond::OPTION_LOWER => Cond::OPTION_LOWER, Cond::OPTION_LIKE_BOTH => Cond::OPTION_LIKE_BOTH));
    }

    public function testTitleEn()
    {
        $query = OrpheaQuery::create()->asset_title_en('Title');
        
        $this->assertEquals($query->getPart(BaseQuery::INNER_JOIN), array(
                '<vr.translate_asset>' => array(
                    'tableName'  => OrpheaQuery::TABLE_TRANSLATE,
                    'schemaName' => null,
                    'cond' => OrpheaQuery::F_OBJ_ID_OBJET . ' = ' . OrpheaQuery::F_TRANSLATE_ID_OBJET,
                    'cols' => null,
                ),
        ));
        $cond = $query->getPart(BaseQuery::WHERE);
        $this->assertEquals($cond->getName(),  OrpheaQuery::F_TRANSLATE_TITLE);
        $this->assertEquals($cond->getOp(),    Cond::OP_LIKE);
        $this->assertEquals($cond->getValue(), 'title');
        $this->assertEquals($cond->getOptions(), array(Cond::OPTION_LOWER => Cond::OPTION_LOWER, Cond::OPTION_LIKE_BOTH => Cond::OPTION_LIKE_BOTH));
    }

    public function testDescription()
    {
        $query = OrpheaQuery::create()->asset_description('Описание');
        
        $cond = $query->getPart(BaseQuery::WHERE);
        $this->assertEquals($cond->getName(),  OrpheaQuery::F_OBJ_DESCRIPTION);
        $this->assertEquals($cond->getOp(),    Cond::OP_LIKE);
        $this->assertEquals($cond->getValue(), 'описание');
        $this->assertEquals($cond->getOptions(), array(Cond::OPTION_LOWER => Cond::OPTION_LOWER, Cond::OPTION_LIKE_BOTH => Cond::OPTION_LIKE_BOTH));
    }

    public function testDescriptionEN()
    {
        $query = OrpheaQuery::create()->asset_description_en('Description');
        
        $this->assertEquals($query->getPart(BaseQuery::INNER_JOIN), array(
                '<vr.translate_asset>' => array(
                    'tableName'  => OrpheaQuery::TABLE_TRANSLATE,
                    'schemaName' => null,
                    'cond' => OrpheaQuery::F_OBJ_ID_OBJET . ' = ' . OrpheaQuery::F_TRANSLATE_ID_OBJET,
                    'cols' => null,
                ),
        ));
        $cond = $query->getPart(BaseQuery::WHERE);
        $this->assertEquals($cond->getName(),  OrpheaQuery::F_TRANSLATE_DESCRIPTION);
        $this->assertEquals($cond->getOp(),    Cond::OP_LIKE);
        $this->assertEquals($cond->getValue(), 'description');
        $this->assertEquals($cond->getOptions(), array(Cond::OPTION_LOWER => Cond::OPTION_LOWER, Cond::OPTION_LIKE_BOTH => Cond::OPTION_LIKE_BOTH));
    }

    public function testOriginal()
    {
        $query = OrpheaQuery::create()->originalMedia('Оригинал');
        
        $this->assertEquals($query->getPart(BaseQuery::INNER_JOIN), array(
                '<orphea.description>' => array(
                    'tableName'  => OrpheaQuery::TABLE_DESC,
                    'schemaName' => null,
                    'cond' => OrpheaQuery::F_OBJ_ID_OBJET . ' = ' . OrpheaQuery::F_DESC_ID_OBJET,
                    'cols' => null,
                ),
        ));
        $cond = $query->getPart(BaseQuery::WHERE);
        $this->assertEquals($cond->getName(),  OrpheaQuery::F_DESC_ORIGINAL);
        $this->assertEquals($cond->getOp(),    Cond::OP_LIKE);
        $this->assertEquals($cond->getValue(), 'оригинал');
        $this->assertEquals($cond->getOptions(), array(Cond::OPTION_LOWER => Cond::OPTION_LOWER, Cond::OPTION_ATOM_STR => Cond::OPTION_ATOM_STR, Cond::OPTION_LIKE_BOTH => Cond::OPTION_LIKE_BOTH));
    }

    public function testConstrain()
    {
        $query = OrpheaQuery::create()->constrainTable();

        $cond = $query->getPart(BaseQuery::WHERE);

        $this->assertTrue(is_null($cond->getName()));
        $this->assertEquals($cond->getOp(), Cond::OP_AND);

        $val = $cond->getValue();
        $this->assertEquals(count($val), 4);
        $this->assertEquals($val[0]->getName(), OrpheaQuery::F_OBJ_PUBLIC);
        $this->assertEquals($val[1]->getName(), OrpheaQuery::F_OBJ_PUBBEGINDATE);
        $this->assertEquals($val[2]->getName(), OrpheaQuery::F_OBJ_PUBENDDATE);
        $this->assertEquals($val[3]->getName(), OrpheaQuery::F_OBJ_ID_STOCK);
    }

    public function testTranslated()
    {
        $query = OrpheaQuery::create()->translated(0);
        
        $this->assertEquals($query->getPart(BaseQuery::INNER_JOIN), array(
                '<orphea.description>' => array(
                    'tableName'  => OrpheaQuery::TABLE_DESC,
                    'schemaName' => null,
                    'cond' => OrpheaQuery::F_OBJ_ID_OBJET . ' = ' . OrpheaQuery::F_DESC_ID_OBJET,
                    'cols' => null,
                ),
        ));
        $cond = $query->getPart(BaseQuery::WHERE);
        $this->assertEquals($cond->getName(),  OrpheaQuery::F_DESC_TRANSLATED);
        $this->assertEquals($cond->getOp(),    Cond::OP_IN);
        $this->assertEquals($cond->getValue(), array(0,2));
        $this->assertEquals($cond->getOptions(), array(Cond::OPTION_NVL => 0));
    }

    public function testLang()
    {
        $query = OrpheaQuery::create()->contentLang('en');
        
        $this->assertEquals($query->getPart(BaseQuery::INNER_JOIN), array(
                '<orphea.description>' => array(
                    'tableName'  => OrpheaQuery::TABLE_DESC,
                    'schemaName' => null,
                    'cond' => OrpheaQuery::F_OBJ_ID_OBJET . ' = ' . OrpheaQuery::F_DESC_ID_OBJET,
                    'cols' => null,
                ),
        ));
        $cond = $query->getPart(BaseQuery::WHERE);
        $this->assertEquals($cond->getName(),  OrpheaQuery::F_DESC_LANG);
        $this->assertEquals($cond->getOp(),    Cond::OP_EQUAL);
        $this->assertEquals($cond->getValue(), 'en');
        $this->assertEquals($cond->getOptions(), array(Cond::OPTION_NVL => 'ru', Cond::OPTION_ATOM_STR => Cond::OPTION_ATOM_STR));
    }

    public function testDateObjet()
    {
        $query = OrpheaQuery::create()->dateObjet('2013-06-01 00:00:00', Cond::OP_MORE);
        
        $cond = $query->getPart(BaseQuery::WHERE);
        $this->assertEquals($cond->getName(),  OrpheaQuery::F_OBJ_DATE_OBJET);
        $this->assertEquals($cond->getOp(),    Cond::OP_MORE);
        $this->assertEquals($cond->getValue()->format('Y-m-d H:i:s'), '2013-06-01 00:00:00');
    }

    public function testDatetimeCreated()
    {
        $query = OrpheaQuery::create()->dateCreated('2013-06-01 00:00:00', Cond::OP_MORE);
        
        $cond = $query->getPart(BaseQuery::WHERE);
        $this->assertEquals($cond->getName(),  OrpheaQuery::F_IPTC_DATETIME);
        $this->assertEquals($cond->getOp(),    Cond::OP_MORE);
        $this->assertEquals($cond->getValue()->format('Y-m-d H:i:s'), '2013-06-01 00:00:00');
    }

    public function testHistoryContext()
    {
        $query = OrpheaQuery::create()->historyContextFilter('2013-06-01 00:00:00', 1234, 'next');

        $cond = $query->getPart(BaseQuery::WHERE);

        $this->assertTrue(is_null($cond->getName()));
        $this->assertEquals($cond->getOp(), Cond::OP_OR);

        $val = $cond->getValue();
        $this->assertEquals(count($val), 2);
        $this->assertEquals($val[0]->getName(), OrpheaQuery::F_OBJ_DATE_OBJET);
        $this->assertTrue($val[1] instanceof JoinCond);
        $this->assertEquals($val[1]->getOp(), Cond::OP_AND);

        $valCond = $val[1]->getValue();
        $this->assertEquals($valCond[0]->getName(), OrpheaQuery::F_OBJ_DATE_OBJET);
        $this->assertEquals($valCond[1]->getName(), OrpheaQuery::F_OBJ_ID_OBJET);
    }

    public function testBaseContext()
    {
        $query = OrpheaQuery::create()->baseContextFilter('2013-06-01 00:00:00', 1234, 'next');

        $cond = $query->getPart(BaseQuery::WHERE);

        $this->assertTrue(is_null($cond->getName()));
        $this->assertEquals($cond->getOp(), Cond::OP_OR);

        $val = $cond->getValue();
        $this->assertEquals(count($val), 2);
        $this->assertEquals($val[0]->getName(), OrpheaQuery::F_IPTC_DATETIME);
        $this->assertTrue($val[1] instanceof JoinCond);
        $this->assertEquals($val[1]->getOp(), Cond::OP_AND);

        $valCond = $val[1]->getValue();
        $this->assertEquals($valCond[0]->getName(), OrpheaQuery::F_IPTC_DATETIME);
        $this->assertEquals($valCond[1]->getName(), OrpheaQuery::F_OBJ_ID_OBJET);
    }

    public function testSearchContext()
    {
        $query = OrpheaQuery::create()->searchContextFilter('2013-06-01 00:00:00', 1234, 'next');

        $cond = $query->getPart(BaseQuery::WHERE);

        $this->assertTrue(is_null($cond->getName()));
        $this->assertEquals($cond->getOp(), Cond::OP_OR);

        $val = $cond->getValue();
        $this->assertEquals(count($val), 2);
        $this->assertEquals($val[0]->getName(), OrpheaQuery::F_IPTC_DATETIME);
        $this->assertTrue($val[1] instanceof JoinCond);
        $this->assertEquals($val[1]->getOp(), Cond::OP_AND);

        $valCond = $val[1]->getValue();
        $this->assertEquals($valCond[0]->getName(), OrpheaQuery::F_IPTC_DATETIME);
        $this->assertEquals($valCond[1]->getName(), OrpheaQuery::F_OBJ_ID_OBJET);
    }

    public function testFullText()
    {
        $query = OrpheaQuery::create()->fullTextSearch('По всему тексту');
        
        $cond = $query->getPart(BaseQuery::WHERE);
        $this->assertEquals($cond->getName(),  OrpheaQuery::F_OBJ_FULL_TEXT);
        $this->assertEquals($cond->getOp(),    Cond::OP_EQUAL);
        $this->assertEquals($cond->getValue(), 'По всему тексту');
    }

    public function testFullTextEn()
    {
        $query = OrpheaQuery::create()->fullTextSearch_en('From all text');
        
        $this->assertEquals($query->getPart(BaseQuery::INNER_JOIN), array(
                '<vr.translate_asset>' => array(
                    'tableName'  => OrpheaQuery::TABLE_TRANSLATE,
                    'schemaName' => null,
                    'cond' => OrpheaQuery::F_OBJ_ID_OBJET . ' = ' . OrpheaQuery::F_TRANSLATE_ID_OBJET,
                    'cols' => null,
                ),
        ));
        $cond = $query->getPart(BaseQuery::WHERE);
        $this->assertEquals($cond->getName(),  OrpheaQuery::F_TRANSLATE_FULL_TEXT);
        $this->assertEquals($cond->getOp(),    Cond::OP_EQUAL);
        $this->assertEquals($cond->getValue(), 'From all text');
    }

    public function testOrderBy()
    {
        $query = OrpheaQuery::create()->orderBy(OrpheaQuery::ORDER_TRANSLATED);
        
        $this->assertEquals($query->getPart(BaseQuery::INNER_JOIN), array(
                '<orphea.iptc>' => array(
                    'tableName'  => OrpheaQuery::TABLE_IPTC,
                    'schemaName' => null,
                    'cond' => OrpheaQuery::F_OBJ_ID_OBJET . ' = ' . OrpheaQuery::F_IPTC_ID_OBJET,
                    'cols' => null,
                ),
                '<orphea.description>' => array(
                    'tableName'  => OrpheaQuery::TABLE_DESC,
                    'schemaName' => null,
                    'cond' => OrpheaQuery::F_OBJ_ID_OBJET . ' = ' . OrpheaQuery::F_DESC_ID_OBJET,
                    'cols' => null,
                ),
        ));
        $this->assertEquals($query->getPart(BaseQuery::ORDER), array(
            array(OrpheaQuery::F_DESC_TRANSLATED, BaseQuery::DESC),
            array(OrpheaQuery::F_IPTC_DATETIME,   BaseQuery::DESC),
            array(OrpheaQuery::F_OBJ_ID_OBJET,    BaseQuery::DESC),
        ));
    }
}
