<?php

/*
Класс для работы с исходным файлом и формирование результирующего файла
*/
class BigStore
{
    private $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function build(Index $index, ChunkStore $store)
    {
        $this->config->getProfiler()->start(__METHOD__);
        $data = array();
        $count = 0;
        $chunkNumber = 1;
        $chunkSize = $this->config->chunk_size;

        $f = fopen($this->config->source_file, 'r');
        while (($row = fgets($f, 4096)) !== false)
        {
            $data[] = (int) $row;
            $count++;

            if ($count == $chunkSize)
            {
                $chunk = new Chunk($this->config, $store);
                sort($data);
                $chunk->setId($chunkNumber)->setSortedData($data)->save();
                $index->setChunk($chunk);

                $data = array();
                $count = 0;
                $chunkNumber++;
            }
        }

        if ($data)
        {
            $chunk = new Chunk($this->config, $store);
            sort($data);
            $chunk->setId($chunkNumber)->setSortedData($data)->save();
            $index->setChunk($chunk);
        }
        $this->config->getProfiler()->end(__METHOD__);
    }

    public function save(array $ids, $store)
    {
        $this->config->getProfiler()->start(__METHOD__);
        $i = 0;
        $f = fopen($this->config->result_file, 'w');
        foreach ($ids as $id)
        {
            $chunk = $store->load($id);
            fwrite($f, ($i++ > 0 ? ChunkStore::DELIMITER : '') . implode(ChunkStore::DELIMITER, $chunk->getData()));
        }
        fclose($f);
        $this->config->getProfiler()->end(__METHOD__);
    }

    public function generateSourceFile()
    {
        if (! $this->config->generate_source_file)
        {
            return;
        }
        $this->config->getProfiler()->start(__METHOD__);
        file_put_contents($this->config->source_file, '');
        $data = array();
        $size = $this->config->chunk_size;
        $count = $this->config->generate_source_file;
        for ($i = 1; $i <= $count; $i++)
        {
            $data[] = rand(0, $count);
            if ($i % $size == 0)
            {
                file_put_contents($this->config->source_file, ($i > $size ? ChunkStore::DELIMITER : '') . implode($data, ChunkStore::DELIMITER), FILE_APPEND);
                $data = array();
            }
        }
        if ($data)
        {
            file_put_contents($this->config->source_file, ($i > $size ? ChunkStore::DELIMITER : '') . implode($data, ChunkStore::DELIMITER), FILE_APPEND);
        }
        $this->config->getProfiler()->end(__METHOD__);
    }
}
