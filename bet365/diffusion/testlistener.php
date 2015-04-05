<?php
if (!defined('ROOT_DEFINED')) {
    define('ROOT_DEFINED', 1);
    $ROOT = __DIR__ . '/../../../';
}

date_default_timezone_set('Europe/Moscow');

include_once $ROOT . 'config/config.php';
include_once $ROOT . 'config/globals.php';

//include_once $ROOT . "scaners/bet365flash/diffusion/dumpListener.php";
include_once $ROOT . "scaners/bet365flash/diffusion/diffListener.php";
include_once $ROOT . "scaners/bet365flash/scanner.php";

define('BET365FLASH_DIFFUSION_DAEMON_PERIOD',     60, true);

set_time_limit(0);
echo date("d.m.Y H:i:s ------------\n");

$listener = new Bet365FlashDiffusionDiffListener();
$listener->setDaemonCallback('daemonCallback');
$listener->debug = true;
$listener->process();

function daemonCallback($scanner, $started)
{
    $profiler = TimeProfiler::instance();
    echo $profiler->getStat() . "\n";
    $profiler->clear();
}
