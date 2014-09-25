<?php

require_once 'IStore.php';

/*
Хранение и загрузка кусков
*/
class ChunkStore implements IStore
{
    const DELIMITER = "\n";
    private $config;
    private $registry;

    public function __construct(Config $config, Registry $registry)
    {
        $this->config = $config;
        $this->registry = $registry;
    }

    private function _dirPath($id)
    {
        return $this->config->tmp_dir . '/' . (floor($id / 100));
    }

    public function save(IObjectSave $chunk)
    {
        $dirPath = $this->_dirPath($chunk->getId());
        if (! file_exists($dirPath)) {
            mkdir($dirPath, 0777, true);
        }
        $this->config->getProfiler()->start(__METHOD__);
        file_put_contents($dirPath . '/' . $chunk->getId(), implode(self::DELIMITER, $chunk->getData()));
        $this->config->getProfiler()->end(__METHOD__);
        return $this;
    }

    public function load($id)
    {
        if ($this->config->registry_cache_count_items)
        {
            return $this->_loadFromRegistry($id);
        }
        else
        {
            return $this->_loadFromStore($id);
        }
    }

    private function _loadFromRegistry($id)
    {
        $this->config->getProfiler()->start(__METHOD__);
        if (! $chunk = $this->registry->get($id))
        {
            $chunk = $this->_loadFromStore($id);
            $this->registry->set($chunk);
        }
        $this->config->getProfiler()->end(__METHOD__);
        return $chunk;
    }

    private function _loadFromStore($id)
    {
        $this->config->getProfiler()->start(__METHOD__);
        $data = explode(self::DELIMITER, $this->_loadData($id));
        $chunk = new Chunk($this->config, $this);
        $chunk->setId($id)->setSortedData($data);
        $this->config->getProfiler()->end(__METHOD__);
        return $chunk;
    }

    private function _loadData($id)
    {
        $this->config->getProfiler()->start(__METHOD__);
        $path = $this->_dirPath($id) . '/' . $id;
        if (! file_exists($path)) {
            throw new Exception('File not found: ' . $path);
        }
        $res = file_get_contents($path);
        $this->config->getProfiler()->end(__METHOD__);
        return $res;
    }
}
