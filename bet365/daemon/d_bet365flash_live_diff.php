<?php
if (!defined('ROOT_DEFINED')) {
    define('ROOT_DEFINED', 1);
    $ROOT = __DIR__ . '/../../../';
}

date_default_timezone_set('Europe/Moscow');

require_once $ROOT . 'config/config.php';
require_once $ROOT . 'scaners/scandaemon/scandaemon.php';
require_once $ROOT . 'scaners/bet365flash/diffusion/diffListener.php';

$period = isset($_SERVER['argv'][2]) ? $_SERVER['argv'][2] : 0.5;
if (! defined('BET365FLASH_DIFFUSION_DAEMON_PERIOD')) {
    define('BET365FLASH_DIFFUSION_DAEMON_PERIOD', $period, true);
}
if (! defined('BET365FLASH_DIFFUSION_SUBSCRIBED_MAX')) {
    define('BET365FLASH_DIFFUSION_SUBSCRIBED_MAX', 50 , true);
}

$scanner = new Bet365FlashDiffusionDiffListener();

$daemonConf = scanDaemon_config::getInstance();
$daemonConf->SCANFOL_ENABLED = true;
$daemonConf->SCANFOL_PORT = 10000;
$daemonConf->SCANFOL_IP = '127.0.0.1';
$daemonConf->SCANERS_LOGS_PATH = '/var/log/betmill/scanners';

$daemonConf->SCANER_NAME = 'Bet365Flash';
$daemonConf->SCANER_MODULE_NAME = 'LiveDiff';
$daemonConf->SCANER_PERIOD = $period;

$daemonConf->SCANER_LINKS = array(
    'site_url'         => '5.226.180.9',
    'test_url'         => SITE_ROOT_URL . 'scaners/bet365flash/',
    'betradar-result'  => SITE_ROOT_URL . 'scaners/betradar-lineview.php?line=' . $scanner->SCANNER_MEMCACHE_NAME,
    'memcached-result' => SITE_ROOT_URL . 'scaners/BookmakerLine/BookmakerLine2json.php?line=' . $scanner->SCANNER_MEMCACHE_NAME,
);

$daemonConf->SCANER_LOGS = array(
    'mapping' => $daemonConf->SCANERS_LOGS_PATH . '/' . $daemonConf->SCANER_NAME . ($daemonConf->SCANER_MODULE_NAME ? '/' . $daemonConf->SCANER_MODULE_NAME : '') . '/Mapping',
    'curl'    => $daemonConf->SCANERS_LOGS_PATH . '/' . $daemonConf->SCANER_NAME . ($daemonConf->SCANER_MODULE_NAME ? '/' . $daemonConf->SCANER_MODULE_NAME : '') . '/Curl'
);

$daemonConf->SCANER_CONTROL_COMMANDS = array(
    'stop'    => 'service bet365flash-live-diff stop',
    'start'   => 'service bet365flash-live-diff start',
    'restart' => 'service bet365flash-live-diff restart',
);

$daemonConf->SCANER_ADDITIONAL_PARAMS = array(
    'some_additional_param' => ''
);

$daemon = $daemonConf->runDaemon();

$scanner->log(coloredText($daemonConf->SCANER_NAME . ' ' . $daemonConf->SCANER_MODULE_NAME . ' Daemon Started', 'green') . ' [period = ' . $period . " sec]");

$cycle_number = 1;
$period = $period * 1e6;

$scanner->setDaemonCallback('daemonCallback');
$scanner->process();

function daemonCallback($scanner, $started)
{
    $profiler = TimeProfiler::instance();
    echo $profiler->getStat() . "\n";
    $profiler->clear();

    global $cycle_number, $period, $daemon;
    $scanner->log(coloredText("Cycle {$cycle_number} Started", 'light cyan'));

    $cycle_report = array();
    $cycle_report['type'] = $daemon::$GLOBAL_CYCLE_REPORT_TYPE;
    $cycle_report['start_time'] = floor($started);

    foreach ($scanner->unresolved as $sportId => $mnemonics) {
        foreach ($mnemonics as $mnemonicName => $mnemonicCount) {
            $daemon->addCannotMapData('Unknown mnemonic: ' . $mnemonicName, $sportId);
        }
    }

    if ($scanner->totalEvents && $scanner->totalOutcomes) {
        $message = "Events: {$scanner->totalEvents}, Outcomes: {$scanner->totalOutcomes}, Connections: {$scanner->totalConnections}";
        $resultCode = $daemon::$RESULT_OK;
    } else {
        $message = "Empty data";
        $resultCode = $daemon::$RESULT_EMPTY;
    }

    $next_start = time();

    $to_sleep = 0;

    if (($to_sleep = $period - (microtime_float() - $started) * 1e6) > 0) {
        $next_start = $next_start + round($to_sleep / 1e6);
    } elseif ($to_sleep < 0) {
        $to_sleep = 0;
    }

    $daemon->datedLog('mapping', $scanner->mappingLog);

    $scanner->log($message);
    $scanner->log("Next Run: " . date('d.m.Y H:i:s', $next_start) . ", " . $scanner->memUsage());
    $scanner->log("Cycle Finished [" . round(microtime_float() - $started, 2) . " sec]");

    $cycle_report['estimated_time'] = time() - $cycle_report['start_time'];
    $cycle_report['result_code'] = $resultCode;
    $cycle_report['next_start_time'] = $next_start;
    $cycle_report['message'] = $message;
    $daemon->send_data2scanfoll(array('cycle_report' => $cycle_report));

    $cycle_number++;
}
