<?php

require_once 'IObjectId.php';
require_once 'IObjectSave.php';

/*
Кусок большого исходного файла
Данные внутри хранятся в отсортированном виде
Алгоритм работает по методу слияния двух отсортированных кусков
*/
class Chunk implements IObjectId, IObjectSave
{
    private $config;
    private $store;

    private $id;
    private $data;

    public function __construct(Config $config, ChunkStore $store = null)
    {
        $this->config = $config;
        $store && $this->store  = $store;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getUniqueKey()
    {
        return $this->getId();
    }

    public function setId($id)
    {
        $this->id = (int) $id;
        return $this;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getMin()
    {
        return $this->data[0];
    }

    public function getMax()
    {
        return $this->data[count($this->data) - 1];
    }

    public function setSortedData(array $data)
    {
        $this->data = $data;
        return $this;
    }

    public function save(IStore $store = null)
    {
        $store || $store = $this->store;
        if (! $store)
        {
            throw new Exception('Need ChunkStore. Define param or __construct');
        }
        $store->save($this);
        return $this;
    }

    public static function sortedMerge(Chunk $chunk1, Chunk $chunk2)
    {
        $p1 = $p2 = 0;
        $data1 = $chunk1->getData();
        $data2 = $chunk2->getData();
        $count1 = count($data1);
        $count2 = count($data2);
        $sortedData = array();

        while ($p1 < $count1 && $p2 < $count2)
        {
            if ($data1[$p1] < $data2[$p2])
            {
                $sortedData[] = $data1[$p1++];
            }
            else
            {
                $sortedData[] = $data2[$p2++];
            }
        }
        while ($p1 < $count1)
        {
            $sortedData[] = $data1[$p1++];
        }
        while ($p2 < $count2)
        {
            $sortedData[] = $data2[$p2++];
        }
        if (count($sortedData) != $count1 + $count2)
        {
            throw new Exception('Fake count');
        }
        $chunk1->setSortedData(array_slice($sortedData, 0, $count1));
        $chunk2->setSortedData(array_slice($sortedData, $count1, $count2));
    }
}
