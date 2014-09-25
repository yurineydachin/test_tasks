<?php

require_once APPLICATION_PATH . '/common/models/Filter/Exceptions.php';

abstract class Cond
{
    const OP_EQUAL      = '=';
    const OP_NOT_EQUAL  = '!=';
    const OP_LESS       = '<';
    const OP_LESS_EQUAL = '<=';
    const OP_MORE       = '>';
    const OP_MORE_EQUAL = '>=';
    const OP_LIKE       = 'LIKE';
    const OP_IN         = 'IN';
    const OP_NOT_IN     = 'NOT IN';
    const OP_OR         = 'OR';
    const OP_AND        = 'AND';
    const OP_DEFAULT    = self::OP_EQUAL;

    const OPTION_LOWER     = 'LOWER';
    const OPTION_NVL       = 'NVL';
    const OPTION_FULL_TEXT = 'FULL_TEXT';
    const OPTION_LIKE_RIGHT = 'LIKE_RIGHT'; // q%
    const OPTION_LIKE_BOTH  = 'LIKE_BOTH'; // %q%
    const OPTION_ATOM_STR   = 'ATOM_STR';

    protected $value; // mixed
    protected $op = self::OP_DEFAULT; // this->getOps
    protected $name;  // string
    protected $options = array();  // option => value

    public function getOps() // by default
    {
        return array(
            self::OP_EQUAL,
            self::OP_NOT_EQUAL,
            self::OP_IN,
            self::OP_NOT_IN,
        );
    }

    public static function getOpsAll()
    {
        return array(
            self::OP_EQUAL,
            self::OP_NOT_EQUAL,
            self::OP_LESS,
            self::OP_LESS_EQUAL,
            self::OP_MORE,
            self::OP_MORE_EQUAL,
            self::OP_LIKE,
            self::OP_IN,
            self::OP_NOT_IN,
            self::OP_OR,
            self::OP_AND,
        );
    }

    public static function create($name = null, $op = null, $value = null)
    {
        // PHP 5.3.0 Late static building
        return new static($name, $op, $value);
    }

    public function __construct($name = null, $op = null, $value = null)
    {
        ! is_null($name)  && $this->setName($name);
        ! is_null($op)    && $this->setOp($op);
        ! is_null($value) && $this->setValue($value);
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($val)
    {
        $this->name = (string) $val;
        return $this;
    }

    public function getOp()
    {
        return $this->op;
    }

    public function setOp($val)
    {
        if (in_array($val, $this->getOps())) {
            $this->op = $val;
        } else {
            throw new CondOpException($val);
        }
        return $this;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setValue($val)
    {
        $op = $this->getOp();
        if (is_array($val) && count($val) > 1)
        {
            if (! in_array($op, array(self::OP_IN, self::OP_NOT_IN)) && array_intersect(array(self::OP_IN, self::OP_NOT_IN), $this->getOps()))
            {
                if ($op == self::OP_NOT_EQUAL) {
                    $this->setOp(self::OP_NOT_IN);
                } else {
                    $this->setOp(self::OP_IN);
                }
            }
            $this->value = $val;
        }
        else
        {
            if (is_array($val)) {
                $val = count($val) ? $val[0] : null;
            }
            if ($op == self::OP_IN) {
                $this->setOp(self::OP_EQUAL);
            } else if ($op == self::OP_NOT_IN) {
                $this->setOp(self::OP_NOT_EQUAL);
            }
            $this->value = $val;
        }
        return $this;
    }

    public function isEqualValue($val)
    {
        return $val === $this->getValue();
    }

    public function addOption($option, $val = null)
    {
        $this->options[$option] = is_null($val) ? $option : $val;
        return $this;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function cleanOption($option)
    {
        unset($this->options[$option]);
        return $this;
    }
}
