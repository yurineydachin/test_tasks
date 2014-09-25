<?php

require_once APPLICATION_PATH . '/common/models/Filter/Cond.php';
require_once APPLICATION_PATH."/common/models/Filter/IntCond.php";
require_once APPLICATION_PATH."/common/models/Filter/StrCond.php";
require_once APPLICATION_PATH."/common/models/Filter/DateCond.php";
require_once APPLICATION_PATH."/common/models/Filter/BoolCond.php";

class JoinCond extends Cond
{
    public function getOps()
    {
        return array(
            self::OP_OR,
            self::OP_AND,
        );
    }

    public function __construct($cond1 = null, $op = null, $cond2 = null)
    {
        $this->value = array();
        $this->op = self::OP_AND; // default operation

        $value = array();
        ! is_null($cond1) && $value[] = $cond1;
        ! is_null($cond2) && $value[] = $cond2;

        parent::__construct(null, $op, $value);
    }

    public function add($val)
    {
        if (is_array($val))
        {
            foreach ($val as $v)
            {
                if ($v instanceof Cond) {
                    $this->value[] = $v;
                } else {
                    throw new CondValueException($v);
                }
            }
        }
        elseif ($val instanceof Cond)
        {
            $this->value[] = $val;
        } else {
            throw new CondValueException($val);
        }
        return $this;
    }

    public function setValue($val)
    {
        $this->value = array();
        return $this->add($val);
    }

    public function addOption($option, $val = null)
    {
        throw new CondOptionException($option);
    }
}
