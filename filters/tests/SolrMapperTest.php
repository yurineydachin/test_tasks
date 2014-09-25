<?php
require_once LIBRARY_PATH.'/phpunit/ControllerTest.php';
require_once APPLICATION_PATH."/common/models/Filter/SolrMapper.php";
require_once APPLICATION_PATH."/configs/orpheaLibrariesDevelopment.php";

class SolrMapperTest extends Zend_Test_PHPUnit_ControllerTestCase
{
    public function testEmpty()
    {
        $query = OrpheaQuery::create();
        $mapper = SolrMapper::create($query);
        $this->assertTrue($mapper instanceof SolrMapper);
        $this->assertEquals($mapper->getQuery(), $query);
        $this->assertEquals($mapper->render(), array(
            'start' => 0,
            'rows'  => SolrMapper::ROWS,
            'q'     => '*:*',
        ));
    }

    public function testRenderFrom()
    {
        $query = OrpheaQuery::create()->fromObjets(array('id_objet', 'titre_court'));
        $mapper = SolrMapper::create($query);

        $this->assertEquals($mapper->render(), array(
            'fl'    => 'id_objet,titre_court',
            'start' => 0,
            'rows'  => SolrMapper::ROWS,
            'q'     => '*:*',
        ));
    }

    public function testRenderFromAll()
    {
        $query = OrpheaQuery::create()->fromObjets(BaseQuery::SQL_WILDCARD);
        $mapper = SolrMapper::create($query);

        $this->assertEquals($mapper->render(), array(
            'start' => 0,
            'rows'  => SolrMapper::ROWS,
            'q'     => '*:*',
        ));
    }

    public function testRenderFromAlias()
    {
        $query = OrpheaQuery::create()->fromObjets(array('id' => 'id_objet', 'titre_court'));
        $mapper = SolrMapper::create($query);

        $this->assertEquals($mapper->render(), array(
            'fl'    => 'id:id_objet,titre_court',
            'start' => 0,
            'rows'  => SolrMapper::ROWS,
            'q'     => '*:*',
        ));
    }

    public function testRenderFromMapping()
    {
        $query = OrpheaQuery::create()->fromObjets(array(OrpheaQuery::F_OBJ_ID_OBJET, OrpheaQuery::F_OBJ_TITLE));
        $mapper = SolrMapper::create($query);

        $this->assertEquals($mapper->render(), array(
            'fl'    => 'id_objet,titre_court',
            'start' => 0,
            'rows'  => SolrMapper::ROWS,
            'q'     => '*:*',
        ));
    }

    public function testRenderJoin()
    {
        $query = OrpheaQuery::create()->fromObjets(array('id_objet', 'titre_court'))->joinIptc(array('datetime_created'));
        $mapper = SolrMapper::create($query);

        $this->assertEquals($mapper->render(), array(
            'fl'    => 'id_objet,titre_court,datetime_created',
            'start' => 0,
            'rows'  => SolrMapper::ROWS,
            'q'     => '*:*',
        ));
    }

    public function testOrderDefault()
    {
        $query = OrpheaQuery::create()->fromObjets('id_objet')->orderBy();
        $mapper = SolrMapper::create($query);

        $this->assertEquals($mapper->render(), array(
            'fl'    => 'id_objet',
            'sort'  => 'datetime_created DESC,id_objet DESC',
            'start' => 0,
            'rows'  => SolrMapper::ROWS,
            'q'     => '*:*',
        ));
    }

    public function testLimit()
    {
        $query = OrpheaQuery::create()->fromObjets('id_objet')->limit(36, 10);
        $mapper = SolrMapper::create($query);

        $this->assertEquals($mapper->render(), array(
            'fl'    => 'id_objet',
            'start' => 10,
            'rows'  => 36,
            'q'     => '*:*',
        ));
    }

    public function testWhereIntEqual0()
    {
        $cond = IntCond::create(OrpheaQuery::F_OBJ_ID_LIASSE, Cond::OP_EQUAL, 0);
        $query = OrpheaQuery::create()->fromObjets('id_objet')->where($cond);
        $mapper = SolrMapper::create($query);

        $this->assertEquals($cond->getName(), OrpheaQuery::F_OBJ_ID_LIASSE);
        $this->assertEquals($cond->getOp(),   Cond::OP_EQUAL);
        $this->assertTrue($cond->getValue() === 0);
        $this->assertFalse(is_null($cond->getValue()));

        $this->assertEquals($mapper->render(), array(
            'fl'    => 'id_objet',
            'start' => 0,
            'rows'  => SolrMapper::ROWS,
            'q'     => 'id_liasse:0',
        ));
    }

    public function testWhereIsNull()
    {
        $query = OrpheaQuery::create()->fromObjets('id_objet')
            ->where(IntCond::create(OrpheaQuery::F_OBJ_ID_LIASSE, Cond::OP_EQUAL, null));
        $mapper = SolrMapper::create($query);

        $this->assertEquals($mapper->render(), array(
            'fl'    => 'id_objet',
            'start' => 0,
            'rows'  => SolrMapper::ROWS,
            'q'     => '!id_liasse:[* TO *]',
        ));
    }

    public function testWhereIsNotNull()
    {
        $query = OrpheaQuery::create()->fromObjets('id_objet')
            ->where(IntCond::create(OrpheaQuery::F_OBJ_ID_LIASSE, Cond::OP_NOT_EQUAL, null));
        $mapper = SolrMapper::create($query);

        $this->assertEquals($mapper->render(), array(
            'fl'    => 'id_objet',
            'start' => 0,
            'rows'  => SolrMapper::ROWS,
            'q'     => 'id_liasse:[* TO *]',
        ));
    }

    public function testWhereIntEqual()
    {
        $query = OrpheaQuery::create()->fromObjets('id_objet')->feature(1234);
        $mapper = SolrMapper::create($query);

        $this->assertEquals($mapper->render(), array(
            'fl'    => 'id_objet',
            'start' => 0,
            'rows'  => SolrMapper::ROWS,
            'q'     => 'id_liasse:1234',
        ));
    }

    public function testWhereIntMore()
    {
        $query = OrpheaQuery::create()->fromObjets('id_objet')
            ->where(IntCond::create(OrpheaQuery::F_OBJ_ID_LIASSE, Cond::OP_MORE, 100));
        $mapper = SolrMapper::create($query);

        $this->assertEquals($mapper->render(), array(
            'fl'    => 'id_objet',
            'start' => 0,
            'rows'  => SolrMapper::ROWS,
            'q'     => 'id_liasse:[101 TO *]',
        ));
    }

    public function testWhereIntMoreEqual()
    {
        $query = OrpheaQuery::create()->fromObjets('id_objet')
            ->where(IntCond::create(OrpheaQuery::F_OBJ_ID_LIASSE, Cond::OP_MORE_EQUAL, 100));
        $mapper = SolrMapper::create($query);

        $this->assertEquals($mapper->render(), array(
            'fl'    => 'id_objet',
            'start' => 0,
            'rows'  => SolrMapper::ROWS,
            'q'     => 'id_liasse:[100 TO *]',
        ));
    }

    public function testWhereArrayInt()
    {
        $query = OrpheaQuery::create()->fromObjets('id_objet')->feature(array(0,1,2,3));
        $mapper = SolrMapper::create($query);

        $this->assertEquals($mapper->render(), array(
            'fl'    => 'id_objet',
            'start' => 0,
            'rows'  => SolrMapper::ROWS,
            'q'     => '(id_liasse:0) OR (id_liasse:1) OR (id_liasse:2) OR (id_liasse:3)',
        ));
    }

    public function testWhereNotArrayInt()
    {
        $query = OrpheaQuery::create()->fromObjets('id_objet')
            ->where(IntCond::create(OrpheaQuery::F_OBJ_ID_LIASSE, Cond::OP_NOT_IN, array(1,2,3)));
        $mapper = SolrMapper::create($query);

        $this->assertEquals($mapper->render(), array(
            'fl'    => 'id_objet',
            'start' => 0,
            'rows'  => SolrMapper::ROWS,
            'q'     => '(!id_liasse:1) AND (!id_liasse:2) AND (!id_liasse:3)',
        ));
    }

    public function testWhereDateEqual()
    {
        $query = OrpheaQuery::create()->fromObjets('id_objet')->dateCreated('2013-01-01 15:23:39', Cond::OP_EQUAL);
        $mapper = SolrMapper::create($query);

        $this->assertEquals($mapper->render(), array(
            'fl'    => 'id_objet',
            'start' => 0,
            'rows'  => SolrMapper::ROWS,
            'q'     => 'datetime_created:[2013-01-01T15:23:39Z+3HOURS TO 2013-01-01T15:23:39Z+3HOURS]',
        ));
    }

    public function testWhereDateLess()
    {
        $query = OrpheaQuery::create()->fromObjets('id_objet')->dateCreated('2013-01-01 15:23:39', Cond::OP_LESS);
        $mapper = SolrMapper::create($query);

        $this->assertEquals($mapper->render(), array(
            'fl'    => 'id_objet',
            'start' => 0,
            'rows'  => SolrMapper::ROWS,
            'q'     => 'datetime_created:[* TO 2013-01-01T15:23:38Z+3HOURS]',
        ));
    }

    public function testWhereDateLessEqual()
    {
        $query = OrpheaQuery::create()->fromObjets('id_objet')->dateCreated('2013-01-01 15:23:39', Cond::OP_LESS_EQUAL);
        $mapper = SolrMapper::create($query);

        $this->assertEquals($mapper->render(), array(
            'fl'    => 'id_objet',
            'start' => 0,
            'rows'  => SolrMapper::ROWS,
            'q'     => 'datetime_created:[* TO 2013-01-01T15:23:39Z+3HOURS]',
        ));
    }

    public function testWhereBoolEqual()
    {
        $query = OrpheaQuery::create()->fromObjets('id_objet')
            ->where(BoolCond::create('public', Cond::OP_EQUAL, true));
        $mapper = SolrMapper::create($query);

        $this->assertEquals($mapper->render(), array(
            'fl'    => 'id_objet',
            'start' => 0,
            'rows'  => SolrMapper::ROWS,
            'q'     => 'public:true',
        ));
    }

    public function testWhereStrEqual()
    {
        $query = OrpheaQuery::create()->fromObjets('id_objet')->asset_title('Заголовок фото');
        $mapper = SolrMapper::create($query);

        $this->assertEquals($mapper->render(), array(
            'fl'    => 'id_objet',
            'start' => 0,
            'rows'  => SolrMapper::ROWS,
            'q'     => '(titre_court:заголовок) AND (titre_court:фото)',
        ));
    }

    public function testWhereStrLike()
    {
        $query = OrpheaQuery::create()->fromObjets('id_objet')->asset_title('Загол% фото', Cond::OP_LIKE);
        $mapper = SolrMapper::create($query);

        $this->assertEquals($mapper->render(), array(
            'fl'    => 'id_objet',
            'start' => 0,
            'rows'  => SolrMapper::ROWS,
            'q'     => '(titre_court:загол*) AND (titre_court:фото)',
        ));
    }

    public function testWhereStrOr()
    {
        $query = OrpheaQuery::create()->fromObjets('id_objet')->asset_title('автомобиль или моторная лодка');
        $mapper = SolrMapper::create($query);

        $this->assertEquals($mapper->render(), array(
            'fl'    => 'id_objet',
            'start' => 0,
            'rows'  => SolrMapper::ROWS,
            'q'     => '(titre_court:автомобиль) OR ((titre_court:моторная) AND (titre_court:лодка))',
        ));
    }

    public function testWhereStrOrEn()
    {
        $query = OrpheaQuery::create()->fromObjets('id_objet')->asset_title('automobile or motor boat');
        $mapper = SolrMapper::create($query);

        $this->assertEquals($mapper->render(), array(
            'fl'    => 'id_objet',
            'start' => 0,
            'rows'  => SolrMapper::ROWS,
            'q'     => '(titre_court:automobile) OR ((titre_court:motor) AND (titre_court:boat))',
        ));
    }

    public function testWhereStrInSimple()
    {
        $query = OrpheaQuery::create()->fromObjets('id_objet')->source(array('Новости', 'Аврора'));
        $mapper = SolrMapper::create($query);

        $this->assertEquals($mapper->render(), array(
            'fl'    => 'id_objet',
            'start' => 0,
            'rows'  => SolrMapper::ROWS,
            'q'     => '(source:"Новости") OR (source:"Аврора")',
        ));
    }

    public function testWhereStrIn()
    {
        $query = OrpheaQuery::create()->fromObjets('id_objet')->source(array('РИА Новости', 'РИА Новости/Аврора'));
        $mapper = SolrMapper::create($query);

        $this->assertEquals($mapper->render(), array(
            'fl'    => 'id_objet',
            'start' => 0,
            'rows'  => SolrMapper::ROWS,
            'q'     => '(source:"РИА Новости") OR (source:"РИА Новости/Аврора")',
        ));
    }

    public function testWhereStrAtom()
    {
        $query = OrpheaQuery::create()->fromObjets('id_objet')->source('Аврора');
        $mapper = SolrMapper::create($query);

        $this->assertEquals($mapper->render(), array(
            'fl'    => 'id_objet',
            'start' => 0,
            'rows'  => SolrMapper::ROWS,
            'q'     => 'source:"Аврора"',
        ));
    }

    public function testWhereStrAtomSymbols()
    {
        $query = OrpheaQuery::create()->fromObjets('id_objet')->archive_code('GA-K17_2004');
        $mapper = SolrMapper::create($query);

        $this->assertEquals($mapper->render(), array(
            'fl'    => 'id_objet',
            'start' => 0,
            'rows'  => SolrMapper::ROWS,
            'q'     => 'shortstring1:"GA K17_2004"',
        ));
    }

    public function testWhereIgnorePublic()
    {
        $query = OrpheaQuery::create()->fromObjets(OrpheaQuery::F_OBJ_ID_OBJET)
            ->where(IntCond::create(OrpheaQuery::F_OBJ_PUBLIC, Cond::OP_MORE, 0));
        $mapper = SolrMapper::create($query);

        $this->assertEquals($mapper->render(), array(
            'fl'    => 'id_objet',
            'start' => 0,
            'rows'  => SolrMapper::ROWS,
            'q'     => '*:*',
        ));
    }

    public function testWhereIgnoreNotInStock()
    {
        $query = OrpheaQuery::create()->fromObjets(OrpheaQuery::F_OBJ_ID_OBJET)
            ->where(IntCond::create(OrpheaQuery::F_OBJ_ID_STOCK, Cond::OP_NOT_IN, array(14, 0)))
            ->where(IntCond::create(OrpheaQuery::F_OBJ_ID_OBJET, Cond::OP_LESS, 10));
        $mapper = SolrMapper::create($query);

        $this->assertEquals($mapper->render(), array(
            'fl'    => 'id_objet',
            'start' => 0,
            'rows'  => SolrMapper::ROWS,
            'q'     => 'id_objet:[* TO 9]',
        ));
    }

    public function testWhereIgnoreDatePub()
    {
        $query = OrpheaQuery::create()->fromObjets(OrpheaQuery::F_OBJ_ID_OBJET)
            ->where(DateCond::create(OrpheaQuery::F_OBJ_PUBBEGINDATE, Cond::OP_LESS, '2013-06-01 00:00:00'))
            ->where(DateCond::create(OrpheaQuery::F_OBJ_PUBENDDATE, Cond::OP_MORE, '2013-07-01 00:00:00'))
            ->where(IntCond::create(OrpheaQuery::F_OBJ_ID_OBJET, Cond::OP_LESS, 10));
        $mapper = SolrMapper::create($query);

        $this->assertEquals($mapper->render(), array(
            'fl'    => 'id_objet',
            'start' => 0,
            'rows'  => SolrMapper::ROWS,
            'q'     => 'id_objet:[* TO 9]',
        ));
    }

    public function testWhereIgnoreJoinCond()
    {
        $query = OrpheaQuery::create()->fromObjets(OrpheaQuery::F_OBJ_ID_OBJET)->constraintable();
        $mapper = SolrMapper::create($query);

        $this->assertEquals($mapper->render(), array(
            'fl'    => 'id_objet',
            'start' => 0,
            'rows'  => SolrMapper::ROWS,
            'q'     => '*:*',
        ));
    }

    public function testWhereNvlContentLangRu()
    {
        $query = OrpheaQuery::create()->fromObjets(OrpheaQuery::F_OBJ_ID_OBJET)->contentLang('ru');
        $mapper = SolrMapper::create($query);

        $this->assertEquals($mapper->render(), array(
            'fl'    => 'id_objet',
            'start' => 0,
            'rows'  => SolrMapper::ROWS,
            'q'     => '(shortstring3:"ru") OR (!shortstring3:[* TO *])',
        ));
    }

    public function testWhereNvlContentLangEn()
    {
        $query = OrpheaQuery::create()->fromObjets(OrpheaQuery::F_OBJ_ID_OBJET)->contentLang('en');
        $mapper = SolrMapper::create($query);

        $this->assertEquals($mapper->render(), array(
            'fl'    => 'id_objet',
            'start' => 0,
            'rows'  => SolrMapper::ROWS,
            'q'     => 'shortstring3:"en"',
        ));
    }

    public function testWhereNvlTranslated()
    {
        $query = OrpheaQuery::create()->fromObjets(OrpheaQuery::F_OBJ_ID_OBJET)
            ->where(IntCond::create(OrpheaQuery::F_DESC_TRANSLATED, Cond::OP_IN, array(0, 2))->addOption(Cond::OPTION_NVL, 0));
        $mapper = SolrMapper::create($query);

        $this->assertEquals($mapper->render(), array(
            'fl'    => 'id_objet',
            'start' => 0,
            'rows'  => SolrMapper::ROWS,
            'q'     => '(shortint1:0) OR (shortint1:2) OR (!shortint1:[* TO *])',
        ));
    }

    public function testWhereNvlTranslatedNotIn()
    {
        $query = OrpheaQuery::create()->fromObjets(OrpheaQuery::F_OBJ_ID_OBJET)
            ->where(IntCond::create(OrpheaQuery::F_DESC_TRANSLATED, Cond::OP_NOT_IN, array(0, 2))->addOption(Cond::OPTION_NVL, 0));
        $mapper = SolrMapper::create($query);

        $this->assertEquals($mapper->render(), array(
            'fl'    => 'id_objet',
            'start' => 0,
            'rows'  => SolrMapper::ROWS,
            'q'     => '(!shortint1:0) AND (!shortint1:2) AND (shortint1:[* TO *])',
        ));
    }

    public function testLightboxContent()
    {
        $query = OrpheaQuery::create()->fromObjets('id_objet')->lightbox(1234);
        $mapper = SolrMapper::create($query);

        $this->assertEquals($mapper->render(), array(
            'fl'    => 'id_objet',
            'start' => 0,
            'rows'  => SolrMapper::ROWS,
            'q'     => 'lightbox_ids:1234',
        ));
    }

    public function testWhereOrientaition()
    {
        $query = OrpheaQuery::create()->fromObjets('id_objet')->orientation('kvadrat');
        $mapper = SolrMapper::create($query);

        $this->assertEquals($mapper->render(), array(
            'fl'    => 'id_objet',
            'start' => 0,
            'rows'  => SolrMapper::ROWS,
            'q'     => '(vertical_percent:[* TO ' . OrpheaQuery::ORIENTATION_TOP . ']) AND (vertical_percent:[' . OrpheaQuery::ORIENTATION_BOTTOM . ' TO *])',
        ));
    }
}
