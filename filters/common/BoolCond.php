<?php

require_once APPLICATION_PATH . '/common/models/Filter/Cond.php';

class BoolCond extends Cond
{
    public function getOps()
    {
        return array(
            self::OP_EQUAL,
            self::OP_NOT_EQUAL,
        );
    }

    public function setValue($val)
    {
        if (is_array($val))
        {
            $val = count($val) > 0;
        }
        else
        {
            $val = (bool) $val;
        }
        return parent::setValue($val);
    }
}
