<?php

require_once APPLICATION_PATH . '/common/models/Filter/Cond.php';

class IntCond extends Cond
{
    public function getOps()
    {
        return array(
            self::OP_EQUAL,
            self::OP_NOT_EQUAL,
            self::OP_LESS,
            self::OP_LESS_EQUAL,
            self::OP_MORE,
            self::OP_MORE_EQUAL,
            self::OP_IN,
            self::OP_NOT_IN,
        );
    }

    public function setValue($val)
    {
        if (is_array($val))
        {
            $newVal = array();
            foreach ($val as $v) {
                $newVal[] = (int) $v;
            }
            $val = $newVal;
        }
        elseif (is_numeric($val))
        {
            $val = (int) $val;
        }
        else
        {
            $val = null;
        }
        return parent::setValue($val);
    }
}
