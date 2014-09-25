<?php

/*
Класс для хранения и работы с мета-данными кусков:
1. выбор двух кусков для слияния
2. проверка отсортированности всех кусков
*/
class Index
{
    private $config;
    private $dataMin = array();
    private $dataMax = array();

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function setChunk(Chunk $chunk)
    {
        $this->dataMin[$chunk->getId()] = $chunk->getMin();
        $this->dataMax[$chunk->getId()] = $chunk->getMax();
        return $this;
    }

    public function getSortedIds()
    {
        return array_keys($this->dataMin);
    }

    public function isSorted()
    {
        $count = count($this->dataMin);
        reset($this->dataMin);
        $prevId  = key($this->dataMin);

        for ($i = 1; $i < $count; $i++)
        {
            if (next($this->dataMin) < $this->dataMax[$prevId])
            {
                return false;
            }
            $prevId  = key($this->dataMin);
        }
        return true;
    }

    public function chooseChunks($store)
    {
        $this->config->getProfiler()->start(__METHOD__);
        asort($this->dataMin); // optimization
        $count = count($this->dataMin);
        reset($this->dataMin);
        $prevId  = key($this->dataMin);

        for ($i = 1; $i < $count; $i++)
        {
            if (next($this->dataMin) < $this->dataMax[$prevId])
            {
                $this->config->getProfiler()->end(__METHOD__);
                return array($store->load($prevId), $store->load(key($this->dataMin)));
            }
            $prevId  = key($this->dataMin);
        }

        throw new Exception('Method isSorted must return false!');
    }
}
