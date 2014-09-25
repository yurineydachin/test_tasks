<?php

require_once APPLICATION_PATH . '/common/models/Filter/Cond.php';

class StrCond extends Cond
{
    const TEXT_OR = '/\s+(или|or|\|)+\s+/iu';
    const TEXT_WORDS = '/[^\w\*а-яё%_\/\']+/iu';
    //-_

    public function getOps()
    {
        return array(
            self::OP_EQUAL,
            self::OP_NOT_EQUAL,
            self::OP_LIKE,
            self::OP_IN,
            self::OP_NOT_IN,
        );
    }

    public function setValue($val)
    {
        $lower = array_key_exists(Cond::OPTION_LOWER, $this->getOptions());
        if (is_array($val))
        {
            $newVal = array();
            foreach ($val as $v) {
                $newVal[] = $lower ? mb_strtolower((string) $v) : (string) $v;
            }
            $val = $newVal;
        }
        elseif (! is_null($val))
        {
            $val = $lower ? mb_strtolower((string) $val) : (string) $val;
        }
        return parent::setValue($val);
    }

    public function addOption($option, $val = null)
    {
        parent::addOption($option, $val);
        if ($option == self::OPTION_LOWER) {
            $this->setValue($this->getValue());
        }
        return $this;
    }
}
