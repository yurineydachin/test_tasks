<?php

require_once 'Config.php';
require_once 'BigStore.php';
require_once 'ChunkStore.php';
require_once 'Chunk.php';
require_once 'Index.php';
require_once 'Registry.php';

/*
Принцип такой:
1. большая куча делится на мелкие
2. мелкие сохраняются в отсортированном виде
3. + в индекс кладется информация о мин\макс значении каждой кучки
4. индекс решает какие две кучки нужно объединить
5. две отсортированные кучки легко и быстро объединяются в отсортированную кучу
6. двойная куча делится попалам и кладется в изначальные
7. возвращаемся к пункту 4
8. если кучки полностью отсортированы, то склеиваем кучки в результитующий файл

Для ускорения было сделано следущее:
1. две пачки для объединения выбираются из отсортированного индекса
2. поскольку индекс отсортирован, то одна и таже пачка подряд обрабатывается несколько раз, поэтому можно использовать кеш для уменьшения колличества обращений чтения\записи с диска

Если кеш = 2, то скорость примерно в 1.5-1.8 раза выше. Большие значения кеша при большом исходном хранилище не даёт эффекта.
*/

$config = new Config();
$bigStore = new BigStore($config);

$bigStore->generateSourceFile();

$index = new Index($config);
$registry = new Registry($config);
$store = new ChunkStore($config, $registry);

$bigStore->build($index, $store);

while (! $index->isSorted())
{
    list($chunk1, $chunk2) = $index->chooseChunks($store);
    $config->getProfiler()->start('Chunk::sortedMerge');
    Chunk::sortedMerge($chunk1, $chunk2);
    $config->getProfiler()->end('Chunk::sortedMerge');
    if (! $config->registry_cache_count_items)
    {
        $chunk1->save();
        $chunk2->save();
    }
    $index->setChunk($chunk1)->setChunk($chunk2);
}

$bigStore->save($index->getSortedIds(), $store);

echo $config->getProfiler()->printReport();
