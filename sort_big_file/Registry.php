<?php

/*
Кеш выталкивающего типа
Нужен для оптимизации загрузок и сохранений - меньше работы с файловой подсистемой.
Если объект давно не запрашивался, то он выталкивается из кеша и сохранятеся.
*/
class Registry
{
    private $config;
    private $cache = array();
    private $stat = array();

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function get($key)
    {
        if (isset($this->cache[$key]))
        {
            $cache = array($key => $this->cache[$key]);
            foreach ($this->cache as $uniqueKey => $uniqueObject)
            {
                $cache[$uniqueKey] = $uniqueObject;
            }
            $this->cache = $cache;
            return $this->cache[$key];
        }
    }

    public function set(IObjectId $object)
    {
        $key = $object->getUniqueKey();
        $cache = array($key => $object);
        $cacheSize = $this->config->registry_cache_count_items;
        $i = 1;
        foreach ($this->cache as $uniqueKey => $uniqueObject)
        {
            if ($uniqueKey != $key && ++$i > $cacheSize)
            {
                $uniqueObject->save();
            }
            else
            {
                $cache[$uniqueKey] = $uniqueObject;
            }
        }
        $this->cache = $cache;
        return $this;
    }
}
