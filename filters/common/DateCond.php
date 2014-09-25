<?php

require_once APPLICATION_PATH . '/common/models/Filter/Cond.php';

class DateCond extends Cond
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
        );
    }

    private function getDate($val)
    {
        if ($val instanceof DateTime) {
            return $val;
        } elseif (is_string($val) && strtotime($val)) {
            return new Datetime($val);
        } else {
            throw new CondDateException($val);
        }
    }

    public function setValue($val)
    {
        if (is_array($val))
        {
            throw new CondDateException('Array');
        }
        else
        {
            $val = $this->getDate($val);
        }
        return parent::setValue($val);
    }

    public function isEqualValue($val)
    {
        if ($this->getValue()) {
            return $this->getDate($val)->format('Y-m-d H:i:s') === $this->getValue()->format('Y-m-d H:i:s');
        } else {
            return $val === $this->getValue();
        }
    }

    public function addOption($option, $val = null)
    {
        if ($option == self::OPTION_NVL) {
            $val = $this->getDate($val);
        }
        return parent::addOption($option, $val);
    }
}
