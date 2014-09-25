<?php

require_once APPLICATION_PATH . '/common/models/Filter/SqlMapper.php';

class VrMapper extends SqlMapper
{
    protected function mapTables() // array(OrpheaQuery::TABLE_* => 'SCHEMA.TABLE',)
    {
        return array(
            OrpheaQuery::TABLE_OBJ  => 'VR.OBJETS_PUBLIC',
            OrpheaQuery::TABLE_IPTC => 'VR.OBJETS_PUBLIC',
            OrpheaQuery::TABLE_DESC => 'VR.OBJETS_PUBLIC',
            OrpheaQuery::TABLE_IC   => 'ORPHEA.IPTC_CATEGORIES',
            OrpheaQuery::TABLE_IK   => 'ORPHEA.IPTC_KEYWORDS',
            OrpheaQuery::TABLE_TRANSLATE => 'VR.TRANSLATE_ASSET',
            OrpheaQuery::TABLE_LIGHTBOX_CONTENT => 'ORPHEA.CONTAINS_OBJECTS',
            OrpheaQuery::TABLE_STOCKS   => 'ORPHEA.STOCKS',
            OrpheaQuery::TABLE_FICHIERS => 'ORPHEA.FICHIERS',
            OrpheaQuery::TABLE_IMAGES   => 'ORPHEA.IMAGES',
        );
    }

    protected function mapFields() // array(OrpheaQuery::F_* => 'TABLE.FIELD',)
    {
        return array(
            OrpheaQuery::F_OBJ_ID_OBJET     => 'OBJETS_PUBLIC.ID_OBJET',
            OrpheaQuery::F_OBJ_ID_STOCK     => 'OBJETS_PUBLIC.ID_STOCK',
            OrpheaQuery::F_OBJ_ID_LIASSE    => 'OBJETS_PUBLIC.ID_LIASSE',
            OrpheaQuery::F_OBJ_ID_TYPE_DOC  => 'OBJETS_PUBLIC.ID_TYPE_DOC',
            OrpheaQuery::F_OBJ_TITLE        => 'OBJETS_PUBLIC.TITRE_COURT',
            OrpheaQuery::F_OBJ_DESCRIPTION  => 'OBJETS_PUBLIC.LEGENDE',
            OrpheaQuery::F_OBJ_PUBLIC       => 'OBJETS_PUBLIC.PUBLIE_INTERNET',
            OrpheaQuery::F_OBJ_PUBBEGINDATE => 'OBJETS_PUBLIC.PUBBEGINDATE',
            OrpheaQuery::F_OBJ_PUBENDDATE   => 'OBJETS_PUBLIC.PUBENDDATE',
            OrpheaQuery::F_OBJ_DATE_OBJET   => 'OBJETS_PUBLIC.DATE_OBJET',
            OrpheaQuery::F_OBJ_DATE_MAJ     => 'OBJETS_PUBLIC.DATE_MAJ',
            OrpheaQuery::F_OBJ_FULL_TEXT    => 'OBJETS_PUBLIC.DUMMY',
            OrpheaQuery::F_IPTC_ID_OBJET    => 'OBJETS_PUBLIC.ID_OBJET',
            OrpheaQuery::F_IPTC_COUNTRY     => 'OBJETS_PUBLIC.COUNTRY',
            OrpheaQuery::F_IPTC_REGION      => 'OBJETS_PUBLIC.PROV_STATE',
            OrpheaQuery::F_IPTC_CITY        => 'OBJETS_PUBLIC.CITY',
            OrpheaQuery::F_IPTC_SOURCE      => 'OBJETS_PUBLIC.SOURCE',
            OrpheaQuery::F_IPTC_AUTHOR      => 'OBJETS_PUBLIC.SIGNATURE',
            OrpheaQuery::F_IPTC_DATETIME    => 'OBJETS_PUBLIC.DATETIME_CREATED',
            OrpheaQuery::F_DESC_ID_OBJET    => 'OBJETS_PUBLIC.ID_OBJET',
            OrpheaQuery::F_DESC_ARCHIVE     => 'OBJETS_PUBLIC.SHORTSTRING1',
            OrpheaQuery::F_DESC_ORIGINAL    => 'OBJETS_PUBLIC.SHORTSTRING2',
            OrpheaQuery::F_DESC_LANG        => 'OBJETS_PUBLIC.SHORTSTRING3',
            OrpheaQuery::F_DESC_TRANSLATED  => 'OBJETS_PUBLIC.SHORTINT1',
            OrpheaQuery::F_IC_ID_OBJET      => 'IPTC_CATEGORIES.ID_OBJET',
            OrpheaQuery::F_IC_ID_ALL_CAT    => 'IPTC_CATEGORIES.ID_ALL_CATEGORIES',
            OrpheaQuery::F_IC_CATEGORIE     => 'IPTC_CATEGORIES.IPTC_CATEGORIE',
            OrpheaQuery::F_IK_ID_OBJET      => 'IPTC_KEYWORDS.ID_OBJET',
            OrpheaQuery::F_IK_ID_ALL_KEY    => 'IPTC_KEYWORDS.ID_ALL_KEYWORD',
            OrpheaQuery::F_IK_KEYWORD       => 'IPTC_KEYWORDS.IPTC_KEYWORD',
            OrpheaQuery::F_TRANSLATE_ID_OBJET    => 'TRANSLATE_ASSET.ASSET_ID',
            OrpheaQuery::F_TRANSLATE_TITLE       => 'TRANSLATE_ASSET.HEADLINE',
            OrpheaQuery::F_TRANSLATE_DESCRIPTION => 'TRANSLATE_ASSET.CAPTION',
            OrpheaQuery::F_TRANSLATE_FULL_TEXT   => 'TRANSLATE_ASSET.DUMMY',
            OrpheaQuery::F_TRANSLATE_KEYWORD     => 'TRANSLATE_ASSET.KEYWORDS',
            OrpheaQuery::F_LC_ID_OBJET      => 'CONTAINS_OBJECTS.ID_OBJET',
            OrpheaQuery::F_LC_ID_LIGHTBOX   => 'CONTAINS_OBJECTS.ID_CHUTIER',
            OrpheaQuery::F_STOCKS_ID_STOCK  => 'STOCKS.ID_STOCK',
            OrpheaQuery::F_STOCKS_DEFAUT    => 'STOCKS.DEFAUT',
            OrpheaQuery::F_FICHIER_ID_FICHIER => 'FICHIERS.ID_FICHIER',
            OrpheaQuery::F_FICHIER_ID_OBJET   => 'FICHIERS.ID_OBJET',
            OrpheaQuery::F_FICHIER_ID_TYPE    => 'FICHIERS.ID_TYPE',
            OrpheaQuery::F_IMAGE_ID_FICHIER   => 'IMAGES.ID_FICHIER',
            OrpheaQuery::F_IMAGE_HEIGHT       => 'IMAGES.HAUTEUR_PIXEL2',
            OrpheaQuery::F_IMAGE_WEIGTH       => 'IMAGES.LARGEUR_PIXEL2',
            OrpheaQuery::F_IMAGE_VERTICAL_PERCENT => 'OBJETS_PUBLIC.VERTICAL_PERCENT',
        );
    }

    private $ignoredTables = array(
        OrpheaQuery::TABLE_IPTC,
        OrpheaQuery::TABLE_DESC,
        OrpheaQuery::TABLE_FICHIERS,
        OrpheaQuery::TABLE_IMAGES,
    );

    // если совпадают все определённые поля этого массива с условием, то оно игнориуется
    // array(array('name' => ... [, 'op' => ... [, 'value' => ...]]),)
    protected function ignoredConds()
    {
        return array(
            array('name' => OrpheaQuery::F_OBJ_PUBLIC, 'op' => Cond::OP_MORE, 'value' => 0),
            array('name' => OrpheaQuery::F_OBJ_ID_STOCK, 'op' => Cond::OP_NOT_IN),
            array('name' => OrpheaQuery::F_OBJ_PUBBEGINDATE),
            array('name' => OrpheaQuery::F_OBJ_PUBENDDATE),
        );
    }

    protected function _renderInnerJoin($items)
    {
        return $this->_renderJoin($items, 'inner');
    }

    protected function _renderLeftJoin($items)
    {
        return $this->_renderJoin($items, 'left');
    }

    private function _renderJoin($items, $type = 'inner')
    {
        $newItems = array();
        $from = $this->getQuery()->getPart(BaseQuery::FROM);
        $ignoreTables = $from && $from['tableName'] == OrpheaQuery::TABLE_OBJ;

        foreach ($items as $item)
        {
            if ($ignoreTables && in_array($item['tableName'], $this->ignoredTables)) {
                $this->getQuery()->columns($item['cols']);
            } else {
                $newItems[] = $item;
            }
        }

        if ($type == 'inner') {
            return parent::_renderInnerJoin($newItems);
        } else {
            return parent::_renderLeftJoin($newItems);
        }
    }

    protected function _renderWhere($where)
    {
        if ($where && $where = $this->ignoreCond($where)) {
            parent::_renderWhere($where);
        }
    }
}
