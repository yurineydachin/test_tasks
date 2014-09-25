<?php

require_once APPLICATION_PATH . '/common/models/Filter/SqlMapper.php';

class OrpheaMapper extends SqlMapper
{
    protected function mapTables() // array(OrpheaQuery::TABLE_* => 'SCHEMA.TABLE',)
    {
        return array(
            OrpheaQuery::TABLE_OBJ  => 'ORPHEA.OBJETS',
            OrpheaQuery::TABLE_IPTC => 'ORPHEA.IPTC',
            OrpheaQuery::TABLE_DESC => 'ORPHEA.DESCRIPTION',
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
            OrpheaQuery::F_OBJ_ID_OBJET     => 'OBJETS.ID_OBJET',
            OrpheaQuery::F_OBJ_ID_STOCK     => 'OBJETS.ID_STOCK',
            OrpheaQuery::F_OBJ_ID_LIASSE    => 'OBJETS.ID_LIASSE',
            OrpheaQuery::F_OBJ_ID_TYPE_DOC  => 'OBJETS.ID_TYPE_DOC',
            OrpheaQuery::F_OBJ_TITLE        => 'OBJETS.TITRE_COURT',
            OrpheaQuery::F_OBJ_DESCRIPTION  => 'OBJETS.LEGENDE',
            OrpheaQuery::F_OBJ_PUBLIC       => 'OBJETS.PUBLIE_INTERNET',
            OrpheaQuery::F_OBJ_PUBBEGINDATE => 'OBJETS.PUBBEGINDATE',
            OrpheaQuery::F_OBJ_PUBENDDATE   => 'OBJETS.PUBENDDATE',
            OrpheaQuery::F_OBJ_DATE_OBJET   => 'OBJETS.DATE_OBJET',
            OrpheaQuery::F_OBJ_DATE_MAJ     => 'OBJETS.DATE_MAJ',
            OrpheaQuery::F_OBJ_FULL_TEXT    => 'OBJETS.DUMMY',
            OrpheaQuery::F_IPTC_ID_OBJET    => 'IPTC.ID_OBJET',
            OrpheaQuery::F_IPTC_COUNTRY     => 'IPTC.COUNTRY',
            OrpheaQuery::F_IPTC_REGION      => 'IPTC.PROV_STATE',
            OrpheaQuery::F_IPTC_CITY        => 'IPTC.CITY',
            OrpheaQuery::F_IPTC_SOURCE      => 'IPTC.SOURCE',
            OrpheaQuery::F_IPTC_AUTHOR      => 'IPTC.SIGNATURE',
            OrpheaQuery::F_IPTC_DATETIME    => 'IPTC.DATE_CREATED',
            OrpheaQuery::F_IPTC_DATE_CREATED => 'IPTC.DATE_CREATED',
            OrpheaQuery::F_IPTC_CREATED_TIME => 'IPTC.CREATED_TIME',
            OrpheaQuery::F_DESC_ID_OBJET    => 'DESCRIPTION.ID_OBJET',
            OrpheaQuery::F_DESC_ARCHIVE     => 'DESCRIPTION.SHORTSTRING1',
            OrpheaQuery::F_DESC_ORIGINAL    => 'DESCRIPTION.SHORTSTRING2',
            OrpheaQuery::F_DESC_LANG        => 'DESCRIPTION.SHORTSTRING3',
            OrpheaQuery::F_DESC_TRANSLATED  => 'DESCRIPTION.SHORTINT1',
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
            OrpheaQuery::F_IMAGE_VERTICAL_PERCENT => 'ROUND(IMAGES.HAUTEUR_PIXEL2 / IMAGES.LARGEUR_PIXEL2 * 100)',
        );
    }
}
