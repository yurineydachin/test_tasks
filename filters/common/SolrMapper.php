<?php

require_once APPLICATION_PATH . '/common/models/Filter/BaseMapper.php';
require_once APPLICATION_PATH."/common/models/Filter/OrpheaQuery.php";

class SolrMapper extends BaseMapper
{
    const ROWS = 10;

    private $compiledParts = array(
        BaseQuery::COLUMNS => array(),
        BaseQuery::LIMIT   => array(self::ROWS, 0),
        BaseQuery::WHERE   => '*:*',
        BaseQuery::ORDER   => array(),
    );

    private $allowedFromTables = array(
        OrpheaQuery::TABLE_OBJ,
        OrpheaQuery::TABLE_IPTC,
        OrpheaQuery::TABLE_DESC,
        OrpheaQuery::TABLE_LIGHTBOX_CONTENT,
    );

    private $allowedJoinTables = array(
        OrpheaQuery::TABLE_IPTC,
        OrpheaQuery::TABLE_DESC,
        OrpheaQuery::TABLE_IC,
        OrpheaQuery::TABLE_IK,
        OrpheaQuery::TABLE_LIGHTBOX_CONTENT,
        OrpheaQuery::TABLE_FICHIERS,
        OrpheaQuery::TABLE_IMAGES,
    );

    protected function mapFields() // array(OrpheaQuery::F_* => 'TABLE.FIELD',)
    {
        return array(
            OrpheaQuery::F_OBJ_ID_OBJET     => 'id_objet',
            OrpheaQuery::F_OBJ_ID_STOCK     => 'id_stock',
            OrpheaQuery::F_OBJ_ID_LIASSE    => 'id_liasse',
            OrpheaQuery::F_OBJ_ID_TYPE_DOC  => 'id_type_doc',
            OrpheaQuery::F_OBJ_TITLE        => 'titre_court',
            OrpheaQuery::F_OBJ_DESCRIPTION  => 'legende',
            OrpheaQuery::F_OBJ_PUBLIC       => 'publie_internet',
            OrpheaQuery::F_OBJ_PUBBEGINDATE => 'pubbegindate',
            OrpheaQuery::F_OBJ_PUBENDDATE   => 'pubenddate',
            OrpheaQuery::F_OBJ_DATE_OBJET   => 'date_objet',
            OrpheaQuery::F_OBJ_DATE_MAJ     => 'date_maj',
            OrpheaQuery::F_OBJ_FULL_TEXT    => 'text',
            OrpheaQuery::F_IPTC_ID_OBJET    => 'id_objet',
            OrpheaQuery::F_IPTC_COUNTRY     => 'country',
            OrpheaQuery::F_IPTC_REGION      => 'prov_state',
            OrpheaQuery::F_IPTC_CITY        => 'city',
            OrpheaQuery::F_IPTC_SOURCE      => 'source',
            OrpheaQuery::F_IPTC_AUTHOR      => 'signature',
            OrpheaQuery::F_IPTC_DATETIME    => 'datetime_created',
            OrpheaQuery::F_DESC_ID_OBJET    => 'id_objet',
            OrpheaQuery::F_DESC_ARCHIVE     => 'shortstring1',
            OrpheaQuery::F_DESC_ORIGINAL    => 'shortstring2',
            OrpheaQuery::F_DESC_LANG        => 'shortstring3',
            OrpheaQuery::F_DESC_TRANSLATED  => 'shortint1',
            OrpheaQuery::F_IC_ID_OBJET      => 'id_objet',
            OrpheaQuery::F_IC_ID_ALL_CAT    => 'iptc_categorie_ids',
            OrpheaQuery::F_IC_CATEGORIE     => 'iptc_categorie',
            OrpheaQuery::F_IK_ID_OBJET      => 'id_objet',
            OrpheaQuery::F_IK_ID_ALL_KEY    => 'iptc_keyword_ids',
            OrpheaQuery::F_IK_KEYWORD       => 'iptc_keyword',
            OrpheaQuery::F_LC_ID_OBJET      => 'id_objet',
            OrpheaQuery::F_LC_ID_LIGHTBOX   => 'lightbox_ids',
            OrpheaQuery::F_IMAGE_VERTICAL_PERCENT => 'vertical_percent',
        );
    }

    protected function getRenderedParts() // array(part => method,)
    {
        return array(
            BaseQuery::FROM          => '_renderFrom',
            BaseQuery::INNER_JOIN    => '_renderInnerJoin',
            BaseQuery::LEFT_JOIN     => '_renderLeftJoin',
            BaseQuery::COLUMNS       => '_renderColumns',
            BaseQuery::WHERE         => '_renderWhere',
            BaseQuery::ORDER         => '_renderOrder',
            BaseQuery::LIMIT         => '_renderLimit',
            //BaseQuery::GROUP,
            //BaseQuery::HAVING,
        );
    }

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

    public function _renderFrom($item)
    {
        if ($item)
        {
            if (! in_array($item['tableName'], $this->allowedFromTables)) {
                throw new SolrMapperDisallowedTableFromException($item['tableName']);
            }
            $this->compiledParts[BaseQuery::COLUMNS][] = $item['cols'];
        }
    }

    protected function _renderInnerJoin($items)
    {
        return $this->_renderJoin($items);
    }

    protected function _renderLeftJoin($items)
    {
        return $this->_renderJoin($items);
    }

    protected function _renderJoin($items)
    {
        foreach ($items as $item)
        {
            if (! in_array($item['tableName'], $this->allowedJoinTables)) {
                throw new SolrMapperDisallowedTableJoinException($item['tableName']);
            }
            $this->compiledParts[BaseQuery::COLUMNS][] = $item['cols'];
        }
    }

    protected function _renderColumns($items)
    {
        foreach ($items as $item) {
            $this->compiledParts[BaseQuery::COLUMNS][] = $item['cols'];
        }

        $this->compiledParts[BaseQuery::COLUMNS] = $this->mappingFields($this->separateColumns($this->compiledParts[BaseQuery::COLUMNS]));
    }

    protected function separateColumns($allCols)
    {
        $cols = array();
        foreach ($allCols as $col)
        {
            if (! $col || $col == BaseQuery::SQL_WILDCARD) {
                continue;
            }
            elseif (is_array($col))
            {
                foreach ($col as $alias => $field)
                {
                    if (is_numeric($alias)) {
                        $cols[] = $field;
                    } else {
                        $cols[$alias] = $field;
                    }
                }
            }
            else {
                $cols[] = $col;
            }
        }
        return array_unique($cols);
    }

    protected function compileColumns()
    {
        if ($this->compiledParts[BaseQuery::COLUMNS])
        {
            $cols = array();
            foreach ($this->compiledParts[BaseQuery::COLUMNS] as $alias => $col) {
                $cols[] = (is_numeric($alias) ? '' : $alias . ':') . $col;
            }
            return strtolower(implode(',', $cols));
        }
    }

    protected function _renderOrder($items)
    {
        foreach ($items as $item) {
            $this->compiledParts[BaseQuery::ORDER][] = $this->mappingFields($item[0] . ' ' . $item[1]);
        }
    }

    protected function compileOrder()
    {
        if ($this->compiledParts[BaseQuery::ORDER]) {
            return implode(',', $this->compiledParts[BaseQuery::ORDER]);
        }
    }

    protected function _renderLimit($limit)
    {
        if ($limit)
        {
            $count  = $limit[0];
            $offset = $limit[1];

            if ($count || $offset) {
                $this->compiledParts[BaseQuery::LIMIT] = array($limit[0], $limit[1]);
            }
        }
    }

    protected function compileLimit()
    {
        return $this->compiledParts[BaseQuery::LIMIT];
    }

    protected function compileWhere()
    {
        return $this->compiledParts[BaseQuery::WHERE];
    }

    protected function _renderWhere($where)
    {
        if ($where && $where = $this->ignoreCond($where)) {
            $this->compiledParts[BaseQuery::WHERE] = $this->_renderCond($where);
        }
    }

    protected function _renderCond($cond)
    {
        if ($cond instanceof JoinCond) {
            return $this->_renderWhereJoinCond($cond);
        } elseif (is_null($cond->getValue())) {
            return $this->_renderWhereNullValue($cond);
        } elseif (is_bool($cond->getValue())) {
            return $this->_renderWhereBoolValue($cond);
        } elseif (is_array($cond->getValue())) {
            return $this->_renderWhereArrayValue($cond);
        }
        else
        {
            $options = $cond->getOptions();
            if ($cond->getOp() == Cond::OP_EQUAL && array_key_exists(Cond::OPTION_NVL, $options) && $cond->isEqualValue($options[Cond::OPTION_NVL])) {
                return $this->_renderWhereSimpleNvl($cond);
            } else {
                return $this->_renderWhereSimpleValue($cond);
            }
        }
    }

    protected function _renderWhereJoinCond($joinCond)
    {
        $res = array();
        foreach ($joinCond->getValue() as $cond) {
            $res[] = $this->_renderCond($cond);
        }
        if ($res) {
            return '(' . implode(') ' . $joinCond->getOp() . ' (', $res) . ')';
        }
    }

    protected function _renderWhereNullValue($cond)
    {
        $name = $this->mappingFields($cond->getName());
        return ($cond->getOp() == Cond::OP_EQUAL ? '!' : '') . $name . ':[* TO *]';
    }

    protected function _renderWhereBoolValue($cond)
    {
        $name = $this->mappingFields($cond->getName());
        return ($cond->getOp() == Cond::OP_EQUAL ? '' : '!') . $name . ':' . ($cond->getValue() ? 'true' : 'false');
    }

    protected function _renderWhereArrayValue($cond)
    {
        $joinCond = JoinCond::create(null, $cond->getOp() == Cond::OP_IN ? Cond::OP_OR : Cond::OP_AND);
        foreach ($cond->getValue() as $value)
        {
            $simpleCond = clone $cond;
            $joinCond->add($simpleCond->setValue($value)->cleanOption(Cond::OPTION_NVL));
        }

        $options = $cond->getOptions();
        if (array_key_exists(Cond::OPTION_NVL, $options) && in_array($options[Cond::OPTION_NVL], $cond->getValue()))
        {
            $simpleCond = clone $cond;
            $joinCond->add($simpleCond->setValue(null)->cleanOption(Cond::OPTION_NVL));
        }
        return $this->_renderCond($joinCond);
    }

    protected function _renderWhereSimpleNvl($cond)
    {
        $cond1 = clone $cond;
        $cond2 = clone $cond;
        $joinCond = JoinCond::create(
            $cond1->cleanOption(Cond::OPTION_NVL),
            Cond::OP_OR,
            $cond2->setValue(null)->cleanOption(Cond::OPTION_NVL)
        );
        return $this->_renderCond($joinCond);
    }

    protected function _renderWhereJoinStrValue($cond, $values, $op = Cond::OP_AND)
    {
        $revertOp = array(
            Cond::OP_AND => Cond::OP_OR,
            Cond::OP_OR  => Cond::OP_AND,
        );
        $op = $cond->getOp() == Cond::OP_NOT_EQUAL ? $revertOp[$op] : $op;
        $joinCond = JoinCond::create(null, $op);
        foreach ($values as $value)
        {
            $simpleCond = clone $cond;
            $joinCond->add($simpleCond->setValue($value));
        }
        return $this->_renderCond($joinCond);
    }

    protected function _renderWhereSimpleValue($cond)
    {
        if ($cond->getValue() instanceof DateTime) {
            $value = $cond->getValue()->format('Y-m-d\TH:i:s') . 'Z+3HOURS';
        } elseif (is_numeric($cond->getValue())) {
            $value = $cond->getValue();
        } else {
            $value = $cond->getValue();
        }

        $valLeft  = '*';
        $valRight = '*';
        $valOne   = null;
        if (in_array($cond->getOp(), array(Cond::OP_MORE, Cond::OP_MORE_EQUAL)))
        {
            if (is_numeric($cond->getValue()) && $cond->getOp() == Cond::OP_MORE) {
                $valLeft = $value + 1;
            } elseif ($cond->getValue() instanceof DateTime && $cond->getOp() == Cond::OP_MORE) {
                $date = clone $cond->getValue();
                $valLeft = $date->modify('+1 second')->format('Y-m-d\TH:i:s') . 'Z+3HOURS';
            } else {
                $valLeft = $value;
            }
        }
        elseif (in_array($cond->getOp(), array(Cond::OP_LESS, Cond::OP_LESS_EQUAL)))
        {
            if (is_numeric($cond->getValue()) && $cond->getOp() == Cond::OP_LESS) {
                $valRight = $value - 1;
            } elseif ($cond->getValue() instanceof DateTime && $cond->getOp() == Cond::OP_LESS) {
                $date = clone $cond->getValue();
                $valRight = $date->modify('-1 second')->format('Y-m-d\TH:i:s') . 'Z+3HOURS';
            } else {
                $valRight = $value;
            }
        }
        elseif (in_array($cond->getOp(), array(Cond::OP_EQUAL, Cond::OP_NOT_EQUAL, Cond::OP_LIKE)))
        {
            $options = $cond->getOptions();
            if ($cond instanceof StrCond)
            {
                $valuesOr = preg_split(StrCond::TEXT_OR, $cond->getValue(), -1, PREG_SPLIT_NO_EMPTY);
                if (count($valuesOr) > 1) {
                    return $this->_renderWhereJoinStrValue($cond, $valuesOr, Cond::OP_OR);
                }

                $values = preg_split(StrCond::TEXT_WORDS, $cond->getValue(), -1, PREG_SPLIT_NO_EMPTY);
                $value = implode(' ', $values);
                if (array_key_exists(Cond::OPTION_ATOM_STR, $options)) {
                    $value = '"' . $value . '"';
                }

                if (! $values) {
                    throw new SolrMapperException($cond->getValue() . ' => ' . debugItem($values));
                }
                elseif (count($values) > 1 && ! array_key_exists(Cond::OPTION_ATOM_STR, $options))
                {
                    return $this->_renderWhereJoinStrValue($cond, $values);
                }
            }
            if ($cond->getValue() instanceof DateTime) {
                $valLeft  = $value;
                $valRight = $value;
            } elseif ($cond->getOp() == Cond::OP_LIKE) {
                $valOne   = str_replace('%', '*', $value);
            } else {
                $valOne   = $value;
            }
        }
        else {
            throw new CondOpException($cond->getOp());
        }

        $name = $this->mappingFields($cond->getName());
        if (! is_null($valOne)) {
            return sprintf('%s%s:%s', $cond->getOp() == Cond::OP_NOT_EQUAL ? '!' : '', $name, $valOne);
        } else {
            return sprintf('%s%s:[%s TO %s]', $cond->getOp() == Cond::OP_NOT_EQUAL ? '!' : '', $name, $valLeft, $valRight);
        }
    }

    protected function compileParts()
    {
        $res = array();
        if ($cols = $this->compileColumns()) {
            $res['fl'] = $cols;
        }
        if ($order = $this->compileOrder()) {
            $res['sort'] = $order;
        }
        if ($limit = $this->compileLimit()) {
            $res['start'] = $limit[1];
            $res['rows']  = $limit[0];
        }
        if ($where = $this->compileWhere()) {
            $res['q']  = $where;
        }
        return $res;
    }
}
