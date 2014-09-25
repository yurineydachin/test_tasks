<?php

require_once APPLICATION_PATH . '/common/models/Filter/Exceptions.php';
require_once APPLICATION_PATH . '/common/models/Filter/BaseQuery.php';

abstract class BaseMapper
{
    private $query;

    protected function mapTables() // array(OrpheaQuery::TABLE_* => 'SCHEMA.TABLE',)
    {
        throw new MapperRedefineException(__METHOD__, __CLASS__ . '::' . 'mappingTables');
    }

    protected function mapFields() // array(OrpheaQuery::F_* => 'TABLE.FIELD',)
    {
        throw new MapperRedefineException(__METHOD__, __CLASS__ . '::' . 'mappingFields');
    }

    public function __construct(BaseQuery $query)
    {
        $this->query = $query;
    }

    public static function create(BaseQuery $query)
    {
        return new static($query);
    }

    public final function getQuery()
    {
        return $this->query;
    }

    abstract protected function getRenderedParts(); // array(part => method,)

    abstract protected function compileParts();

    // если совпадают все определённые поля этого массива с условием, то оно игнориуется
    // array(array('name' => ... [, 'op' => ... [, 'value' => ...]]),)
    protected function ignoredConds()
    {
        throw new MapperRedefineException(__METHOD__);
    }

    public final function render()
    {
        foreach ($this->getRenderedParts() as $part => $method)
        {
            $this->$method($this->getQuery()->getPart($part));
        }

        return $this->compileParts();
    }

    protected function mappingFields($cols)
    {
        $search  = array_keys($this->mapFields());
        $replace = array_values($this->mapFields());
        if (is_array($cols))
        {
            foreach ($cols as $alias => $col) {
                $cols[$alias] = str_replace($search, $replace, $col);
            }
        } else {
            $cols = str_replace($search, $replace, $cols);
        }
        return $cols;
    }

    protected function mappingTables($table)
    {
        $search  = array_keys($this->mapTables());
        $replace = array_values($this->mapTables());
        return str_replace($search, $replace, $table);
    }

    protected function ignoreCond($cond)
    {
        if (! $this->ignoredConds()) {
            return $cond;
        } elseif ($cond instanceof JoinCond) {
            return $this->ignoreJoinCond($cond);
        } elseif ($cond instanceof Cond) {
            return $this->ignoreSimpleCond($cond);
        } else {
            throw new SqlMapperCondException($cond);
        }
    }

    protected function ignoreSimpleCond($cond)
    {
        foreach ($this->ignoredConds() as $item)
        {
            $cnt = 0;
            foreach ($item as $name => $val)
            {
                if ($name == 'name') {
                    if ($cond->getName() != $val) continue;
                } elseif ($name == 'op') {
                    if ($cond->getOp() != $val) continue;
                } elseif ($name == 'value') {
                    if ($cond->getValue() instanceof DateTime) {
                        if ($cond->getValue()->format('Y-m-d H:i:s') != $val) continue;
                    } else {
                        if ($cond->getValue() != $val) continue;
                    }
                }
                $cnt++;
            }
            if ($cnt == count($item)) {
                return null;
            }
        }
        return $cond;
    }

    protected function ignoreJoinCond($joinCond)
    {
        $newConds = array();
        foreach ($joinCond->getValue() as $cond)
        {
            if ($c = $this->ignoreCond($cond)) {
                $newConds[] = $c;
            }
        }

        if (count($newConds) > 1)
        {
            $resJoinCond = JoinCond::create(null, $joinCond->getOp());
            foreach ($newConds as $cond) {
                $resJoinCond->add($cond);
            }
            return $resJoinCond;
        }
        elseif (count($newConds) == 1) {
            return $newConds[0];
        } else {
            return null;
        }
    }
}
