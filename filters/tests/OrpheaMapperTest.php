<?php
require_once LIBRARY_PATH.'/phpunit/ControllerTest.php';
require_once APPLICATION_PATH."/common/models/Filter/OrpheaMapper.php";
require_once APPLICATION_PATH."/configs/orpheaLibrariesDevelopment.php";
 
class OrpheaMapperTest extends Zend_Test_PHPUnit_ControllerTestCase
{
    public function testEmpty()
    {
        $query = BaseQuery::create();
        $mapper = OrpheaMapper::create($query);
        $this->assertTrue($mapper instanceof OrpheaMapper);
        $this->assertEquals($mapper->getQuery(), $query);
    }

    public function testFrom()
    {
        $query = OrpheaQuery::create()->fromObjets(array(OrpheaQuery::F_OBJ_ID_OBJET, OrpheaQuery::F_OBJ_ID_STOCK));
        $mapper = OrpheaMapper::create($query);

        $this->assertEquals($mapper->render(), 'SELECT "OBJETS"."ID_OBJET", "OBJETS"."ID_STOCK" FROM "ORPHEA"."OBJETS"');
    }

    public function testInnerJoin()
    {
        $query = OrpheaQuery::create()->fromObjets('ID_OBJET')->joinInner('TRANSLATE_ASSET', 'TRANSLATE_ASSET.ASSET_ID = ' . OrpheaQuery::F_OBJ_ID_OBJET, null, 'VR');
        $mapper = OrpheaMapper::create($query);

        $this->assertEquals($mapper->render(), 
            'SELECT "OBJETS"."ID_OBJET" FROM "ORPHEA"."OBJETS"' . "\n"
            . ' INNER JOIN "VR"."TRANSLATE_ASSET" ON TRANSLATE_ASSET.ASSET_ID = OBJETS.ID_OBJET'
        );
    }

    public function testLeftJoin()
    {
        $query = OrpheaQuery::create()->fromObjets('ID_OBJET')->joinLeft('TRANSLATE_ASSET', 'TRANSLATE_ASSET.ASSET_ID = ' . OrpheaQuery::F_OBJ_ID_OBJET, null, 'VR');
        $mapper = OrpheaMapper::create($query);

        $this->assertEquals($mapper->render(), 
            'SELECT "OBJETS"."ID_OBJET" FROM "ORPHEA"."OBJETS"' . "\n"
            . ' LEFT JOIN "VR"."TRANSLATE_ASSET" ON TRANSLATE_ASSET.ASSET_ID = OBJETS.ID_OBJET'
        );
    }

    public function testWhereInt()
    {
        $query = OrpheaQuery::create()->fromObjets(array(OrpheaQuery::F_OBJ_ID_OBJET, OrpheaQuery::F_OBJ_ID_STOCK))->library(12);
        $mapper = OrpheaMapper::create($query);

        $this->assertEquals($mapper->render(), 'SELECT "OBJETS"."ID_OBJET", "OBJETS"."ID_STOCK" FROM "ORPHEA"."OBJETS"'
            . ' WHERE (OBJETS.ID_STOCK = 12)');
    }

    public function testWhereStr()
    {
        $query = OrpheaQuery::create()->fromObjets(array(OrpheaQuery::F_OBJ_ID_OBJET, OrpheaQuery::F_OBJ_ID_STOCK))->asset_title('Заголовок фото%', Cond::OP_LIKE);
        $mapper = OrpheaMapper::create($query);

        $this->assertEquals($mapper->render(), 'SELECT "OBJETS"."ID_OBJET", "OBJETS"."ID_STOCK" FROM "ORPHEA"."OBJETS"'
            . ' WHERE (LOWER(OBJETS.TITRE_COURT) LIKE \'%заголовок фото%\')');
    }

    public function testWhereDate()
    {
        $query = OrpheaQuery::create()->fromObjets(array(OrpheaQuery::F_OBJ_ID_OBJET, OrpheaQuery::F_OBJ_ID_STOCK))->dateObjet('2013-06-01 15:30:55', Cond::OP_LESS_EQUAL);
        $mapper = OrpheaMapper::create($query);

        $this->assertEquals($mapper->render(), 'SELECT "OBJETS"."ID_OBJET", "OBJETS"."ID_STOCK" FROM "ORPHEA"."OBJETS"'
            . ' WHERE (OBJETS.DATE_OBJET <= TO_DATE(\'2013-06-01 15:30:55\', \'YYYY-MM-DD HH24:MI:SS\'))');
    }

    public function testLightboxContent()
    {
        $query = OrpheaQuery::create()->fromObjets('ID_OBJET')->lightbox(array(1,2,3));
        $mapper = OrpheaMapper::create($query);

        $this->assertEquals($mapper->render(), 
            'SELECT "OBJETS"."ID_OBJET" FROM "ORPHEA"."OBJETS"' . "\n"
            . ' INNER JOIN "ORPHEA"."CONTAINS_OBJECTS" ON OBJETS.ID_OBJET = CONTAINS_OBJECTS.ID_OBJET'
            . ' WHERE (CONTAINS_OBJECTS.ID_CHUTIER IN (1, 2, 3))'
        );
    }

    public function testOrientation()
    {
        $query = OrpheaQuery::create()->fromObjets('ID_OBJET')->orientation('kvadrat');
        $mapper = OrpheaMapper::create($query);

        $this->assertEquals($mapper->render(), 
            'SELECT "OBJETS"."ID_OBJET" FROM "ORPHEA"."OBJETS"' . "\n"
            . ' INNER JOIN "ORPHEA"."FICHIERS" ON OBJETS.ID_OBJET = FICHIERS.ID_OBJET AND FICHIERS.ID_TYPE = 3' . "\n"
            . ' INNER JOIN "ORPHEA"."IMAGES" ON FICHIERS.ID_FICHIER = IMAGES.ID_FICHIER'
            . ' WHERE ((ROUND(IMAGES.HAUTEUR_PIXEL2 / IMAGES.LARGEUR_PIXEL2 * 100) <= ' . OrpheaQuery::ORIENTATION_TOP . ') AND (ROUND(IMAGES.HAUTEUR_PIXEL2 / IMAGES.LARGEUR_PIXEL2 * 100) >= ' . OrpheaQuery::ORIENTATION_BOTTOM . '))'
        );
    }
}
