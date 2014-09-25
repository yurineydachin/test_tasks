<?php

require_once APPLICATION_PATH . '/common/models/Filter/Exceptions.php';
require_once APPLICATION_PATH . '/common/models/Filter/IntCond.php';
require_once APPLICATION_PATH . '/common/models/Filter/StrCond.php';
require_once APPLICATION_PATH . '/common/models/Filter/BoolCond.php';
require_once APPLICATION_PATH . '/common/models/Filter/JoinCond.php';
require_once APPLICATION_PATH . '/common/models/Filter/DateCond.php';

/*
 Подобно Zend_Db_Select формирует запрос, но не привязан к конкретной реализации,
 т.е. можно представить этот запрос в виде SQL, Solr, Xml и выполнить его.
*/

class BaseQuery
{
    const COLUMNS        = 'columns';
    const FROM           = 'from';
    const UNION          = 'union';
    const WHERE          = 'where';
    const GROUP          = 'group';
    const HAVING         = 'having';
    const ORDER          = 'order';
    const LIMIT          = 'limit';
    const INNER_JOIN     = 'inner join';
    const LEFT_JOIN      = 'left join';
    const TAGS           = 'tags';

    const ASC        = 'ASC';
    const DESC       = 'DESC';

    const SQL_WILDCARD   = '*';

    protected static $_partsInit = array(
        self::COLUMNS      => array(),
        self::FROM         => null,
        self::INNER_JOIN   => array(),
        self::LEFT_JOIN    => array(),
        self::WHERE        => null,
        self::GROUP        => array(),
        self::HAVING       => array(),
        self::ORDER        => array(),
        self::LIMIT        => null,
    );

    protected $_adapter;

    protected $_parts = array();

    protected $_tableCols = array();

    public static function create($adapter = null)
    {
        // PHP 5.3.0 Late static building
        return new static($adapter);
    }

    public function __construct($adapter = null)
    {
        $this->_adapter = $adapter;
        $this->_parts = self::$_partsInit;
    }

    public function getPart($part)
    {
        $part = strtolower($part);
        return array_key_exists($part, $this->_parts) ? $this->_parts[$part] : null;
    }

    public function getParts()
    {
        return $this->_parts;
    }

    public function from($tableName, $cols = self::SQL_WILDCARD, $schemaName = null)
    {
        $this->_parts[self::FROM] = array(
            'tableName'  => $tableName,
            'schemaName' => $schemaName,
            'cols'       => $cols,
        );
        return $this;
    }

    public function columns($cols = self::SQL_WILDCARD, $tableName = null)
    {
        if ($cols)
        {
            $this->_parts[self::COLUMNS][] = array(
                'tableName' => $tableName,
                'cols' => $cols,
            );
        }
        return $this;
    }

    public function join($tableName, $cond, $cols = self::SQL_WILDCARD, $schemaName = null)
    {
        return $this->joinInner($tableName, $cond, $cols, $schemaName);
    }

    public function joinInner($tableName, $cond, $cols = self::SQL_WILDCARD, $schemaName = null)
    {
        return $this->_join(self::INNER_JOIN, $tableName, $cond, $cols, $schemaName);
    }

    public function joinLeft($tableName, $cond, $cols = self::SQL_WILDCARD, $schemaName = null)
    {
        return $this->_join(self::LEFT_JOIN, $tableName, $cond, $cols, $schemaName);
    }

    protected function _join($type, $tableName, $cond, $cols, $schemaName = null)
    {
        $this->_parts[$type][strtolower($tableName)] = array(
            'tableName'  => $tableName,
            'schemaName' => $schemaName,
            'cond'       => $cond,
            'cols'       => $cols,
        );
        return $this;
    }

    public function where(Cond $cond)
    {
        return $this->_where($cond, Cond::OP_AND);
    }

    public function orWhere(Cond $cond)
    {
        return $this->_where($cond, Cond::OP_OR);
    }

    protected function _where(Cond $cond, $join = Cond::OP_AND)
    {
        if (! $this->_parts[self::WHERE]) {
            $this->_parts[self::WHERE] = $cond;
        } elseif ($this->_parts[self::WHERE] instanceof JoinCond && $this->_parts[self::WHERE]->getOp() == $join) {
            $this->_parts[self::WHERE]->add($cond);
        } else {
            $this->_parts[self::WHERE] = JoinCond::create(null, $join)
                ->add($this->_parts[self::WHERE])
                ->add($cond);
        }
        return $this;
    }

    public function group($spec)
    {
        if (! is_array($spec)) {
            $spec = array($spec);
        }

        foreach ($spec as $val) {
            $this->_parts[self::GROUP][] = $val;
        }
        return $this;
    }

    public function having($cond)
    {
        $this->_parts[self::HAVING][] = $cond;
        return $this;
    }

    public function order($col, $direction = self::ASC)
    {
        $this->_parts[self::ORDER][] = array(
            $col,
            $direction,
        );
        return $this;
    }

    public function limit($count = null, $offset = null)
    {
        $this->_parts[self::LIMIT] = array((int) $count, (int) $offset);
        return $this;
    }

    /**
     * Назначить теги запросу
     * @return BaseQuery 
     */
    public function tags(/* args */)
    {
        foreach (func_get_args() as $value)
        {
            if (is_array($value)) {
                call_user_func_array(array($this, 'tags'), $value);
            } elseif (is_string($value) && in_array($value, $this->_parts[self::TAGS]) == false) {
               $this->_parts[self::TAGS][] = $value;
            }
        }
        return $this;
    }

    public function reset($part)
    {
        if (array_key_exists($part, self::$_partsInit))
        {
            $this->_parts[$part] = self::$_partsInit[$part];

            if ($part == self::COLUMNS)
            {
                $this->_parts[self::FROM]['cols'] = null;
                foreach ($this->_parts[self::INNER_JOIN] as &$item) {
                    $item['cols'] = null;
                }
                foreach ($this->_parts[self::LEFT_JOIN] as &$item) {
                    $item['cols'] = null;
                }
            }
        }
    }

    public function fetchCol($cache = true)
    {
        return $this->_adapter->fetchCol($this, $cache);
    }

    public function fetchRow($cache = true)
    {
        return $this->_adapter->fetchRow($this, $cache);
    }

    public function fetchAll($cache = true)
    {
        return $this->_adapter->fetchAll($this, $cache);
    }

    public function fetchOne($cache = true)
    {
        return $this->_adapter->fetchOne($this, $cache);
    }

    public function render()
    {
        return $this->_apapter->renderQuery($this);
    }
}
