<?php
if (!defined('ROOT_DEFINED')) {
    define('ROOT_DEFINED', 1);
    $ROOT = __DIR__ . '/../../../';
}

date_default_timezone_set('Europe/Moscow');

include_once $ROOT . 'config/config.php';
include_once $ROOT . 'config/globals.php';

include_once $ROOT . "scaners/bet365flash/diffusion/dumpListener.php";

$profiler = TimeProfiler::instance();
$pTotalKey = $profiler->start(TimeProfiler::total);

set_time_limit(0);
echo date("d.m.Y H:i:s ------------\n");

$listener = new Bet365FlashDiffusionDumpListener();
$listener->debug = true;
print_r($listener->getDump());

$profiler->stop(TimeProfiler::total, $pTotalKey);
//echo 'profiler: ' . $profiler->getLogTimesString() . "\n";
echo $profiler->getStat() . "\n";
$profiler->clear();
