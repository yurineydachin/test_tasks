<?php

require_once APPLICATION_PATH."/common/models/Filter/BaseQuery.php";

/*
* Класс предназначен для формирования select-запросов к оригинальной схеме
* базы данных орфея, инкапсулируя в себе всю логику связи таблиц и условий.
*/
class OrpheaQuery extends \BaseQuery
{
    const ORDER_API_CREATION  = 'api_creation';
    const ORDER_LIGHTBOX_DATE = 'lightbox_date';
    const ORDER_NEXT          = 'next';
    const ORDER_PREV          = 'prev';
    const ORDER_ID_NEXT       = 'id_next';
    const ORDER_HISTORIC_NEXT = 'historic_next';
    const ORDER_HISTORIC_PREV = 'historic_prev';
    const ORDER_TRANSLATED    = 'translated';
    const ORDER_DEFAULT       = self::ORDER_NEXT;

    const TABLE_OBJ = '<ORPHEA.OBJETS>';
    const F_OBJ_ID_OBJET     = '<O.ID_OBJET>';
    const F_OBJ_ID_STOCK     = '<O.ID_STOCK>';
    const F_OBJ_ID_LIASSE    = '<O.ID_LIASSE>';
    const F_OBJ_ID_TYPE_DOC  = '<O.ID_TYPE_DOC>';
    const F_OBJ_TITLE        = '<O.TITRE_COURT>';
    const F_OBJ_DESCRIPTION  = '<O.LEGENDE>';
    const F_OBJ_PUBLIC       = '<O.PUBLIE_INTERNET>';
    const F_OBJ_PUBBEGINDATE = '<O.PUBBEGINDATE>';
    const F_OBJ_PUBENDDATE   = '<O.PUBENDDATE>';
    const F_OBJ_DATE_OBJET   = '<O.DATE_OBJET>';
    const F_OBJ_DATE_MAJ     = '<O.DATE_MAJ>';
    const F_OBJ_FULL_TEXT    = '<O.DUMMY>';

    const TABLE_IPTC = '<ORPHEA.IPTC>';
    const F_IPTC_ID_OBJET = '<I.ID_OBJET>';
    const F_IPTC_COUNTRY  = '<I.COUNTRY>';
    const F_IPTC_REGION   = '<I.PROV_STATE>';
    const F_IPTC_CITY     = '<I.CITY>';
    const F_IPTC_SOURCE   = '<I.SOURCE>';
    const F_IPTC_AUTHOR   = '<I.AUTHOR>';
    const F_IPTC_DATETIME = '<I.DATETIME_CREATED>';
    const F_IPTC_DATE_CREATED = '<I.DATE_CREATED>';
    const F_IPTC_CREATED_TIME = '<I.CREATED_TIME>';

    const TABLE_DESC = '<ORPHEA.DESCRIPTION>';
    const F_DESC_ID_OBJET   = '<D.ID_OBJET>';
    const F_DESC_ARCHIVE    = '<D.SHORTSTRING1>';
    const F_DESC_ORIGINAL   = '<D.SHORTSTRING2>';
    const F_DESC_LANG       = '<D.SHORTSTRING3>';
    const F_DESC_TRANSLATED = '<D.SHORTINT1>';

    const TABLE_IC = '<ORPHEA.IPTC_CATEGORIES>';
    const F_IC_ID_OBJET   = '<IC.ID_OBJET>';
    const F_IC_ID_ALL_CAT = '<IC.ID_ALL_CATEGORIES>';
    const F_IC_CATEGORIE  = '<IC.IPTC_CATEGORIE>';

    const TABLE_IK = '<ORPHEA.IPTC_KEYWORDS>';
    const F_IK_ID_OBJET   = '<IK.ID_OBJET>';
    const F_IK_ID_ALL_KEY = '<IK.ID_ALL_KEYWORD>';
    const F_IK_KEYWORD    = '<IK.IPTC_KEYWORD>';

    const TABLE_TRANSLATE = '<VR.TRANSLATE_ASSET>';
    const F_TRANSLATE_ID_OBJET    = '<T.ASSET_ID>';
    const F_TRANSLATE_TITLE       = '<T.HEADLINE>';
    const F_TRANSLATE_DESCRIPTION = '<T.CAPTION>';
    const F_TRANSLATE_FULL_TEXT   = '<T.DUMMY>';
    const F_TRANSLATE_KEYWORD     = '<T.KEYWORDS>';

    const TABLE_LIGHTBOX_CONTENT = '<ORPHEA.CONTAINS_OBJECTS>';
    const F_LC_ID_OBJET    = '<LC.ID_OBJET>';
    const F_LC_ID_LIGHTBOX = '<LC.ID_CHUTIER>';

    const TABLE_STOCKS = '<ORPHEA.STOCKS>';
    const F_STOCKS_ID_STOCK = '<S.ID_STOCK>';
    const F_STOCKS_DEFAUT   = '<S.DEFAUT>';

    const TABLE_FICHIERS = '<ORPHEA.FICHIERS>';
    const F_FICHIER_ID_FICHIER = '<F.ID_FICHIER>';
    const F_FICHIER_ID_OBJET   = '<F.ID_OBJET>';
    const F_FICHIER_ID_TYPE    = '<F.ID_TYPE>';

    const TABLE_IMAGES = '<ORPHEA.IMAGES>';
    const F_IMAGE_ID_FICHIER = '<IM.ID_FICHIER>';
    const F_IMAGE_HEIGHT     = '<IM.HAUTEUR_PIXEL2>';
    const F_IMAGE_WEIGTH     = '<IM.LARGEUR_PIXEL2>';
    const F_IMAGE_VERTICAL_PERCENT = '<IM.VERTICAL_PERCENT>';

    const FILETYPE_ENLARGEMENT = 3;
    const ORIENTATION_TOP = 103;
    const ORIENTATION_BOTTOM = 97;

    public function fromObjets($cols)
    {
        return $this->from(self::TABLE_OBJ, $cols);
    }

    public function joinIptc($cols = null, $left = false)
    {
        if ($left) {
            $this->joinLeft(self::TABLE_IPTC, self::F_OBJ_ID_OBJET . ' = ' . self::F_IPTC_ID_OBJET, $cols);
        } else {
            $this->join(self::TABLE_IPTC, self::F_OBJ_ID_OBJET . ' = ' . self::F_IPTC_ID_OBJET, $cols);
        }
        return $this;
    }

    public function joinDescription($cols = null, $left = false)
    {
        if ($left) {
            $this->joinLeft(self::TABLE_DESC, self::F_OBJ_ID_OBJET . ' = ' . self::F_DESC_ID_OBJET, $cols);
        } else {
            $this->join(self::TABLE_DESC, self::F_OBJ_ID_OBJET . ' = ' . self::F_DESC_ID_OBJET, $cols);
        }
        return $this;
    }

    public function joinFichiers($cols = null, $left = false, $type = null)
    {
        $cond = self::F_OBJ_ID_OBJET . ' = ' . self::F_FICHIER_ID_OBJET;
        if (is_numeric($type)) {
            $cond .= ' AND ' . self::F_FICHIER_ID_TYPE . ' = ' . (int) $type;
        }
        if ($left) {
            $this->joinLeft(self::TABLE_FICHIERS, $cond, $cols);
        } else {
            $this->join(self::TABLE_FICHIERS, $cond, $cols);
        }
        return $this;
    }

    public function joinImages($cols = null, $left = false)
    {
        $cond = self::F_FICHIER_ID_FICHIER . ' = ' . self::F_IMAGE_ID_FICHIER;
        if ($left) {
            $this->joinLeft(self::TABLE_IMAGES, $cond, $cols);
        } else {
            $this->join(self::TABLE_IMAGES, $cond, $cols);
        }
        return $this;
    }

    public function joinTranslate($cols = null, $left = false)
    {
        if ($left) {
            $this->joinLeft(self::TABLE_TRANSLATE, self::F_OBJ_ID_OBJET . ' = ' . self::F_TRANSLATE_ID_OBJET, $cols);
        } else {
            $this->join(self::TABLE_TRANSLATE, self::F_OBJ_ID_OBJET . ' = ' . self::F_TRANSLATE_ID_OBJET, $cols);
        }
        return $this;
    }

    protected function getIgnoreLibraries()
    {
        return array(
            \ORPHEA_LIBRARY_VIDEO_AP,
            \ORPHEA_LIBRARY_DELETED,
            \ORPHEA_LIBRARY_GREEN,
        );
    }

    public function library($ids, array $notIds = array())
    {
        $ids = array_diff((array) $ids, $this->getIgnoreLibraries());
        $ids = array_diff((array) $ids, $notIds);

        if ($ids) {
            $this->where(IntCond::create(self::F_OBJ_ID_STOCK, Cond::OP_IN, $ids));
        } elseif ($notIds) {
            $this->where(IntCond::create(self::F_OBJ_ID_STOCK, Cond::OP_NOT_IN, $notIds));
        }

        return $this;
    }

    public function stockLibrary($ids)
    {
        $ids = array_diff((array) $ids, $this->getIgnoreLibraries());
        if ($ids)
        {
            $this->join(self::TABLE_STOCKS, self::F_OBJ_ID_OBJET . ' = ' . self::F_STOCKS_DEFAUT, $cols);
            $this->where(IntCond::create(self::F_STOCKS_ID_STOCK, Cond::OP_IN, $ids));
        }
        return $this;
    }

    public function country($value)
    {
        return $this->joinIptc()->where(StrCond::create(self::F_IPTC_COUNTRY, Cond::OP_EQUAL, $value)->addOption(Cond::OPTION_ATOM_STR));
    }
    
    public function region($value)
    {
        return $this->joinIptc()->where(StrCond::create(self::F_IPTC_REGION, Cond::OP_EQUAL, $value)->addOption(Cond::OPTION_ATOM_STR));
    }

    public function city($value)
    {
        return $this->joinIptc()->where(StrCond::create(self::F_IPTC_CITY, Cond::OP_EQUAL, $value)->addOption(Cond::OPTION_ATOM_STR));
    }

    public function category($data)
    {
        $this->join(self::TABLE_IC, self::F_OBJ_ID_OBJET . ' = ' . self::F_IC_ID_OBJET, null);
        if (is_array($data) || is_numeric($data)) {
            return $this->where(IntCond::create(self::F_IC_ID_ALL_CAT, Cond::OP_IN, $data));
        } else {
            return $this->where(StrCond::create(self::F_IC_CATEGORIE, Cond::OP_LIKE, $data)->addOption(Cond::OPTION_LIKE_BOTH));
        }
    }

    public function keyword($data)
    {
        $this->join(self::TABLE_IK, self::F_OBJ_ID_OBJET . ' = ' . self::F_IK_ID_OBJET, null);
        if (is_array($data) || is_numeric($data)) {
            return $this->where(IntCond::create(self::F_IK_ID_ALL_KEY, Cond::OP_IN, $data));
        } else {
            return $this->where(StrCond::create(self::F_IK_KEYWORD, Cond::OP_LIKE, $data)->addOption(Cond::OPTION_LOWER)->addOption(Cond::OPTION_LIKE_BOTH));
        }
    }

    public function keyword_en($value)
    {
        return $this->joinTranslate()->where(StrCond::create(self::F_TRANSLATE_KEYWORD, Cond::OP_LIKE, $value)->addOption(Cond::OPTION_LOWER)->addOption(Cond::OPTION_LIKE_BOTH));
    }

    public function asset_id($value)
    {
        return $this->where(IntCond::create(self::F_OBJ_ID_OBJET, Cond::OP_IN, $value));
    }

    public function feature($value)
    {
        return $this->where(IntCond::create(self::F_OBJ_ID_LIASSE, Cond::OP_IN, $value));
    }

    public function lightbox($value)
    {
        $this->join(self::TABLE_LIGHTBOX_CONTENT, self::F_OBJ_ID_OBJET . ' = ' . self::F_LC_ID_OBJET, null);
        return $this->where(IntCond::create(self::F_LC_ID_LIGHTBOX, Cond::OP_IN, $value));
    }

    public function typeDoc($value)
    {
        return $this->where(IntCond::create(self::F_OBJ_ID_TYPE_DOC, Cond::OP_IN, $value));
    }

    protected function whereLike()
    {
        $args = func_get_args();
        $cond = $args[0];
        if (! is_array($cond->getValue()))
        {
            for($i = 1; $i < count($args); $i++)
            {
                if (is_array($args[$i])) {
                    $cond->addOption($args[$i][0], $args[$i][1]);
                } else {
                    $cond->addOption($args[$i]);
                }
            }
        }
        return $this->where($cond);
    }

    public function author($value, $op = Cond::OP_LIKE)
    {
        $cond = StrCond::create(self::F_IPTC_AUTHOR, $op, $value);
        return $this->joinIptc()->whereLike($cond, Cond::OP_LIKE, Cond::OPTION_LOWER, Cond::OPTION_LIKE_BOTH);
    }

    public function source($value, $op = Cond::OP_LIKE)
    {
        $cond = StrCond::create(self::F_IPTC_SOURCE, $op, $value)->addOption(Cond::OPTION_ATOM_STR);
        return $this->joinIptc()->whereLike($cond, Cond::OP_LIKE, Cond::OPTION_LIKE_BOTH);
    }
    
    public function archive_code($value, $op = Cond::OP_LIKE)
    {
        $cond = StrCond::create(self::F_DESC_ARCHIVE, $op, $value)->addOption(Cond::OPTION_ATOM_STR);
        return $this->joinDescription()->whereLike($cond, Cond::OP_LIKE, Cond::OPTION_LIKE_BOTH);
    }

    public function asset_title($value, $op = Cond::OP_LIKE)
    {
        $cond = StrCond::create(self::F_OBJ_TITLE, $op, $value);
        return $this->whereLike($cond, Cond::OP_LIKE, Cond::OPTION_LOWER, Cond::OPTION_LIKE_BOTH);
    }

    public function asset_title_en($value, $op = Cond::OP_LIKE)
    {
        $cond = StrCond::create(self::F_TRANSLATE_TITLE, $op, $value);
        return $this->joinTranslate()->whereLike($cond, Cond::OP_LIKE, Cond::OPTION_LOWER, Cond::OPTION_LIKE_BOTH);
    }

    public function asset_description($value, $op = Cond::OP_LIKE)
    {
        $cond = StrCond::create(self::F_OBJ_DESCRIPTION, $op, $value);
        return $this->whereLike($cond, Cond::OP_LIKE, Cond::OPTION_LOWER, Cond::OPTION_LIKE_BOTH);
    }

    public function asset_description_en($value, $op = Cond::OP_LIKE)
    {
        $cond = StrCond::create(self::F_TRANSLATE_DESCRIPTION, $op, $value);
        return $this->joinTranslate()->whereLike($cond, Cond::OP_LIKE, Cond::OPTION_LOWER, Cond::OPTION_LIKE_BOTH);
    }

    public function originalMedia($value, $op = Cond::OP_LIKE)
    {
        $cond = StrCond::create(self::F_DESC_ORIGINAL, $op, $value)->addOption(Cond::OPTION_ATOM_STR);
        return $this->joinDescription()->whereLike($cond, Cond::OP_LIKE, Cond::OPTION_LIKE_BOTH);
    }

    public function constrainTable()
    {
        return $this->where(IntCond::create(self::F_OBJ_PUBLIC, Cond::OP_MORE, 0))
            ->where(DateCond::create(self::F_OBJ_PUBBEGINDATE,  Cond::OP_LESS, date('Y-m-d 00:00:00')))
            ->where(DateCond::create(self::F_OBJ_PUBENDDATE,    Cond::OP_MORE, date('Y-m-d 23:59:59')))
            ->where(IntCond::create(self::F_OBJ_ID_STOCK,       Cond::OP_NOT_IN, $this->getIgnoreLibraries()));
    }

    public function translated($value = null, $joinLeft = false)
    {
        $this->joinDescription(null, $joinLeft);

        if ($value === null) { // big brother is watching you!
            $this->where(IntCond::create(self::F_DESC_TRANSLATED, Cond::OP_NOT_EQUAL, 1984));
        } elseif ($value === 0) { // непереведенные
            $this->where(IntCond::create(self::F_DESC_TRANSLATED, Cond::OP_IN, array(0, 2))->addOption(Cond::OPTION_NVL, 0));
        } elseif ($value == 1) { // переведенные
            $this->where(IntCond::create(self::F_DESC_TRANSLATED, Cond::OP_EQUAL, 1));
        } elseif ($value == 2) { // непереведенные срочные
            $this->where(IntCond::create(self::F_DESC_TRANSLATED, Cond::OP_EQUAL, 2));
        }
        return $this;
    }

    public function contentLang($lang, $joinLeft = false)
    {
        if ($lang && $lang !== 'all')
        {
            $this->joinDescription(null, $lang === 'ru' && $joinLeft);
            $this->where(StrCond::create(self::F_DESC_LANG, Cond::OP_EQUAL, $lang)->addOption(Cond::OPTION_NVL, 'ru')->addOption(Cond::OPTION_ATOM_STR));
        }
        return $this;
    }

    public function orientation($value = null)
    {
        if ($value && $value !== 'all')
        {
            $this->joinFichiers(null, false, self::FILETYPE_ENLARGEMENT);
            $this->joinImages();
            if ($value == 'vertical') {
                $this->where(IntCond::create(self::F_IMAGE_VERTICAL_PERCENT, Cond::OP_MORE, self::ORIENTATION_TOP));
            } elseif ($value == 'horizontal') {
                $this->where(IntCond::create(self::F_IMAGE_VERTICAL_PERCENT, Cond::OP_LESS, self::ORIENTATION_BOTTOM));
            } else {
                $this->where(IntCond::create(self::F_IMAGE_VERTICAL_PERCENT, Cond::OP_LESS_EQUAL, self::ORIENTATION_TOP));
                $this->where(IntCond::create(self::F_IMAGE_VERTICAL_PERCENT, Cond::OP_MORE_EQUAL, self::ORIENTATION_BOTTOM));
            }
        }
        return $this;
    }

    public function dateObjet($value, $op)
    {
        if ($value instanceof DateTime || is_string($value) && strlen($value) > 10) {
            return $this->where(DateCond::create(self::F_OBJ_DATE_OBJET, $op, $value));
        } elseif ($value) {
            throw new OrpheaException('dateObjet is short: ' . $value);
        }
    }

    public function dateCreated($value, $op)
    {
        if ($value instanceof DateTime || is_string($value) && strlen($value) > 10) {
            return $this->where(DateCond::create(self::F_IPTC_DATETIME, $op, $value));
        } elseif ($value) {
            throw new OrpheaException('dateObjet is short: ' . $value);
        }
    }

    protected function _dateContext($dateField, $date, $id, $direction)
    {
        $op = ($direction == 'next') ? Cond::OP_LESS : Cond::OP_MORE;
        return $this->where(JoinCond::create(
            DateCond::create($dateField, $op, $date),
            Cond::OP_OR,
            JoinCond::create(
                DateCond::create($dateField, Cond::OP_EQUAL, $date),
                Cond::OP_AND,
                IntCond::create(self::F_OBJ_ID_OBJET, $op, $id)
            )
        ));
    }

    public function historyContextFilter($date, $id, $direction)
    {
        return $this->_dateContext(self::F_OBJ_DATE_OBJET, $date, $id, $direction);
    }

    public function baseContextFilter($date, $id, $direction)
    {
        return $this->_dateContext(self::F_IPTC_DATETIME, $date, $id, $direction);
    }

    public function searchContextFilter($date, $id, $direction)
    {
        return $this->_dateContext(self::F_IPTC_DATETIME, $date, $id, $direction);
    }

    public function fullTextSearch($value)
    {
        return $this->where(StrCond::create(self::F_OBJ_FULL_TEXT, Cond::OP_EQUAL, $value)->addOption(Cond::OPTION_FULL_TEXT));
    }

    public function fullTextSearch_en($value)
    {
        return $this->joinTranslate()->where(StrCond::create(self::F_TRANSLATE_FULL_TEXT, Cond::OP_EQUAL, $value)->addOption(Cond::OPTION_FULL_TEXT));
    }

    public function orderBy($name = self::ORDER_DEFAULT)
    {
        $name = $name ? $name : self::ORDER_DEFAULT;
        $sort = in_array($name, array(self::ORDER_PREV, self::ORDER_HISTORIC_PREV)) ? self::ASC : self::DESC;

        if (in_array($name, array(self::ORDER_NEXT, self::ORDER_PREV, self::ORDER_LIGHTBOX_DATE)))
        {
            $this->joinIptc();
            return $this->order(self::F_IPTC_DATETIME, $sort)
                        ->order(self::F_OBJ_ID_OBJET,  $sort);
        }
        elseif (in_array($name, array(self::ORDER_HISTORIC_NEXT, self::ORDER_HISTORIC_PREV)))
        {
            return $this->order(self::F_OBJ_DATE_OBJET, $sort)
                        ->order(self::F_OBJ_ID_OBJET,   $sort);
        }
        elseif ($name == self::ORDER_API_CREATION)
        {
            return $this->order(self::F_OBJ_DATE_MAJ, $sort)
                        ->order(self::F_OBJ_ID_OBJET, $sort);
        }
        elseif ($name == self::ORDER_ID_NEXT)
        {
            return $this->order(self::F_OBJ_ID_OBJET, $sort);
        }
        elseif ($name == self::ORDER_TRANSLATED)
        {
            $this->joinDescription()->joinIptc();
            return $this->order(self::F_DESC_TRANSLATED, $sort)
                        ->order(self::F_IPTC_DATETIME,   $sort)
                        ->order(self::F_OBJ_ID_OBJET,    $sort);
        }
        else {
            throw new Exception('Unknow direction: '.$name);
        }
        return $this->order($order);
    }
}
