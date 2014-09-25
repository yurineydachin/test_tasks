<?php

require_once APPLICATION_PATH . '/common/models/Filter/BaseMapper.php';
require_once APPLICATION_PATH . '/common/models/Filter/OrpheaQuery.php';

abstract class SqlMapper extends BaseMapper
{
    protected $_db;
    protected $_select;

    protected function getRenderedParts() // array(part => method,)
    {
        return array(
            BaseQuery::FROM          => '_renderFrom',
            BaseQuery::INNER_JOIN    => '_renderInnerJoin',
            BaseQuery::LEFT_JOIN     => '_renderLeftJoin',
            BaseQuery::COLUMNS       => '_renderColumns',
            BaseQuery::WHERE         => '_renderWhere',
            BaseQuery::GROUP         => '_renderGroup',
            BaseQuery::HAVING        => '_renderHaving',
            BaseQuery::ORDER         => '_renderOrder',
            BaseQuery::LIMIT         => '_renderLimit',
        );
    }

    public function __construct(BaseQuery $query)
    {
        parent::__construct($query);
        $this->_db = \Zend_Registry::get('Zend_Db');
        $this->_select = $this->_db->select();
    }

    protected function _renderColumns($items)
    {
        foreach ($items as $item)
        {
            $this->_select->columns(
                $this->mappingFields($item['cols']),
                $item['tableName']
            );
        }
    }

    protected function _renderFrom($item)
    {
        if ($item)
        {
            $this->_select->from(
                $this->mappingTables($item['tableName']),
                $this->mappingFields($item['cols']),
                $item['schemaName']
            );
        }
    }

    protected function _renderInnerJoin($items)
    {
        foreach ($items as $item)
        {
            $this->_select->joinInner(
                $this->mappingTables($item['tableName']),
                $this->mappingFields($item['cond']),
                $this->mappingFields($item['cols']),
                $item['schemaName']
            );
        }
    }

    protected function _renderLeftJoin($items)
    {
        foreach ($items as $item)
        {
            $this->_select->joinLeft(
                $this->mappingTables($item['tableName']),
                $this->mappingFields($item['cond']),
                $this->mappingFields($item['cols']),
                $item['schemaName']
            );
        }
    }

    protected function _renderWhere($where)
    {
        if ($where) {
            $this->_select->where($this->_renderCond($where));
        }
    }

    protected function _renderCond($cond)
    {
        if ($cond instanceof JoinCond) {
            return $this->_renderWhereJoinCond($cond);
        } elseif ($cond instanceof IntCond) {
            return $this->_renderWhereSimpleCond($cond);
        } elseif ($cond instanceof StrCond) {
            return $this->_renderWhereSimpleCond($cond);
        } elseif ($cond instanceof DateCond) {
            return $this->_renderWhereDateCond($cond);
        } elseif ($cond instanceof BoolCond) {
            return $this->_renderWhereBoolCond($cond);
        } else {
            throw new MapperCondException($cond);
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

    protected function _renderStrValueFullText($value)
    {
        $valuesOr = $valuesAnd = array();

        if (is_string($value))
        {
            $value = str_replace('*', '%', $value);
            $valuesOr = preg_split(StrCond::TEXT_OR, $value, -1, PREG_SPLIT_NO_EMPTY);
        }

        if (count($valuesOr) > 1)
        {
            $valuesOrRendered = array();
            foreach ($valuesOr as $valueWords) {
                $valuesOrRendered[] = $this->_renderStrValueFullText($valueWords);
            }
            return '(' . implode(') OR (', array_filter($valuesOrRendered)) . ')';
        }
        else
        {
            if (is_string($value)) {
                $valuesAnd = preg_split(StrCond::TEXT_WORDS, $value, -1, PREG_SPLIT_NO_EMPTY);
            } else {
                $valuesAnd = $value;
            }
            $values = array();
            foreach ($valuesAnd as $value)
            {
                if (mb_strtolower($value) != 'and') {
                    $values[] = $value;
                }
            }
            return $values ? '(' . implode(') AND (', $values) . ')' : null;
        }
    }

    protected function _renderWhereSimpleCond($cond)
    {
        $options = $cond->getOptions();
        $name = $this->mappingFields($cond->getName());
        $name = $this->checkOptions($name, $options);
        if (is_null($cond->getValue()))
        {
            return $name . ' ' . ($cond->getOp() == Cond::OP_EQUAL ? 'IS NULL' : 'IS NOT NULL');
        }
        elseif (array_key_exists(Cond::OPTION_FULL_TEXT, $options))
        {
            $value = $this->_renderStrValueFullText($cond->getValue());
            return str_replace('?', $this->_db->quote($value), $name) . ' > 0';
        }
        else
        {
            $value = $cond->getValue();
            if ((array_key_exists(Cond::OPTION_LIKE_RIGHT, $options) || array_key_exists(Cond::OPTION_LIKE_BOTH, $options)) && substr($value, -1, 1) !== '%') {
                $value .= '%';
            }
            if (array_key_exists(Cond::OPTION_LIKE_BOTH, $options) && substr($value, 1, 1) !== '%') {
                $value = '%' . $value;
            }
            if (is_array($value)) {
                $value = '(' . $this->_db->quote($value) . ')';
            } else {
                $value = $this->_db->quote($value);
            }
            return $name . ' ' . $cond->getOp() . ' ' . $value;
        }
    }

    protected function _renderWhereDateCond($cond)
    {
        $name = $this->mappingFields($cond->getName());
        $name = $this->checkOptions($name, $cond->getOptions(), true);
        if (is_null($cond->getValue())) {
            return $name . ' ' . ($cond->getOp() == Cond::OP_EQUAL ? 'IS NULL' : 'IS NOT NULL');
        } else {
            return $name . ' ' . $cond->getOp() . ' TO_DATE(' . $this->_db->quote($cond->getValue()->format('Y-m-d H:i:s')) . ', \'YYYY-MM-DD HH24:MI:SS\')';
        }
    }

    protected function _renderWhereBoolCond($cond)
    {
        $name = $this->mappingFields($cond->getName());
        $name = $this->checkOptions($name, $cond->getOptions());
        return $name . ' ' . $cond->getOp() . ' ' . ($cond->getValue() ? '1' : '0');
    }

    protected function checkOptions($name, $options, $isDate = false)
    {
        if (isset($options[Cond::OPTION_FULL_TEXT])) {
            //$name = 'CONTAINS(' . $name . ', ORPHEA.PACK_ORPHTEXT.STRTRANSLATE(?))';
            $name = 'CONTAINS(' . $name . ', ?)';
        }
        if (isset($options[Cond::OPTION_LOWER])) {
            $name = 'LOWER(' . $name . ')';
        }
        if (isset($options[Cond::OPTION_NVL]))
        {
            if ($isDate) {
                $name = sprintf('NVL(%s, TO_DATE(%s, \'YYYY-MM-DD HH24:MI:SS\'))', $name, $this->_db->quote($options[Cond::OPTION_NVL]->format('Y-m-d H:i:s')));
            } else {
                $name = sprintf('NVL(%s, %s)', $name, $this->_db->quote($options[Cond::OPTION_NVL]));
            }
        }
        return $name;
    }

    protected function _renderGroup($items)
    {
        foreach ($items as $item) {
            $this->_select->group($item);
        }
    }

    protected function _renderHaving($items)
    {
        foreach ($items as $item) {
            $this->_select->having($this->mappingFields($item));
        }
    }

    protected function _renderOrder($items)
    {
        foreach ($items as $item) {
            $this->_select->order($this->mappingFields($item[0] . ' ' . $item[1]));
        }
    }

    protected function _renderLimit($limit)
    {
        if ($limit)
        {
            $count  = $limit[0];
            $offset = $limit[1];

            if ($count || $offset) {
                $this->_select->limit($count, $offset);
            }
        }
    }

    protected function compileParts()
    {
        return $this->_select->__toString();
    }
}
