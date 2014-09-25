<?php
require_once LIBRARY_PATH.'/phpunit/ControllerTest.php';
require_once APPLICATION_PATH."/common/models/Filter/StrMapper.php";
require_once APPLICATION_PATH."/common/models/Filter/OrpheaQuery.php";
 
class StrMapperTest extends Zend_Test_PHPUnit_ControllerTestCase
{
    public function testEmpty()
    {
        $query = OrpheaQuery::create();
        $mapper = StrMapper::create($query);
        $this->assertTrue($mapper instanceof StrMapper);
        $this->assertEquals($mapper->getQuery(), $query);
        $this->assertEquals($mapper->render(), '');
    }

    public function testRenderColumns()
    {
        $query = OrpheaQuery::create();
        $query->columns(array('id_objet', 'titre_court'), 'ORPHEA.OBJETS');
        $query->columns('date_created', 'ORPHEA.IPTC');
        $mapper = StrMapper::create($query);

        $this->assertEquals($mapper->render(), "Columns:\n" .
            StrMapper::TAB . "ORPHEA.OBJETS:id_objet,titre_court\n" .
            StrMapper::TAB . "ORPHEA.IPTC:date_created");
    }
}
