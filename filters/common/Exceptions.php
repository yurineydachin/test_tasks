<?php

class CondException extends Exception
{
}

class CondOpException extends CondException
{
    public function __construct($message)
    {
        parent::__construct('Unsupported conditon operation: ' . $message);
    }
}

class CondValueException extends CondException
{
    public function __construct($message)
    {
        parent::__construct('Unsupported conditon value: ' . $message);
    }
}

class CondOptionException extends CondException
{
    public function __construct($message)
    {
        parent::__construct('Unsupported conditon option: ' . $message);
    }
}

class CondDateException extends CondException
{
    public function __construct($message)
    {
        parent::__construct('Unsupported conditon date: ' . $message);
    }
}

class QueryException extends Exception
{
}

class BaseQueryRedefineException extends QueryException
{
    public function __construct($message)
    {
        parent::__construct('Method shoud be redefined: ' . $message);
    }
}

class MapperException extends Exception
{
}

class BaseMapperRedefineException extends MapperException
{
    public function __construct($message, $message2 = null)
    {
        if ($message2) {
            parent::__construct('Before use the method ' . $message2. ' you shoud redefine ' . $message);
        } else {
            parent::__construct('Method shoud be redefined: ' . $message);
        }
    }
}

class MapperCondException extends MapperException
{
    public function __construct($where)
    {
        parent::__construct('Unsupported where: ' . is_object($where) ? get_class($where) : $where);
    }
}

class SolrMapperException extends MapperException
{
}

class SolrMapperDisallowedTableException extends SolrMapperException
{
    public function __construct($message)
    {
        parent::__construct('Table does not supported: ' . $message);
    }
}

class SolrMapperDisallowedTableFromException extends SolrMapperDisallowedTableException
{
}

class SolrMapperDisallowedTableJoinException extends SolrMapperDisallowedTableException
{
}

class SolrAdapterException extends SolrMapperException
{
}

class SolrAdapterRedefineException extends SolrAdapterException
{
    public function __construct($message)
    {
        parent::__construct('Method shoud be redefined: ' . $message);
    }
}

class SolrAdapterQueryException extends SolrAdapterException
{
    public function __construct($query)
    {
        parent::__construct('Unsupported query: ' . is_object($query) ? get_class($query) : $query);
    }
}

class SolrAdapterResultException extends SolrAdapterException
{
    public function __construct($err, $params, $res = null)
    {
        parent::__construct(($err ? 'Curl error: ' . debugItem($err) . "\n\n" : '') . 'Request params: ' . debugItem($params) . ($res ? "\n\nRESPONCE: " . debugItem($res) : ''));
    }
}
