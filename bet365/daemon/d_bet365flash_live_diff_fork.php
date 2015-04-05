<?php
if (!defined('ROOT_DEFINED')) {
    define('ROOT_DEFINED', 1);
    $ROOT = __DIR__ . '/../../../';
}

date_default_timezone_set('Europe/Moscow');

require_once $ROOT . 'config/config.php';
require_once $ROOT . 'Framework/memory.cache.class.php';
MemoryCache::memcachedON();
require_once $ROOT . 'scaners/scandaemon/scandaemon.php';
require_once $ROOT . 'scaners/bet365flash/diffusion/diffListenerFork.php';

$period = isset($_SERVER['argv'][2]) ? $_SERVER['argv'][2] : 1;
if (! defined('BET365FLASH_DIFFUSION_DAEMON_PERIOD')) {
    define('BET365FLASH_DIFFUSION_DAEMON_PERIOD', $period, true);
}

$daemonConf = scanDaemon_config::getInstance();
$daemonConf->SCANFOL_ENABLED = true;
$daemonConf->SCANFOL_PORT = 10000;
$daemonConf->SCANFOL_IP = '127.0.0.1';
$daemonConf->SCANERS_LOGS_PATH = '/var/log/betmill/scanners';

$daemonConf->SCANER_NAME = 'Bet365Flash';
$daemonConf->SCANER_MODULE_NAME = 'LiveDiffFork';
$daemonConf->SCANER_PERIOD = $period;

$scanner = new Bet365FlashDiffusionDiffListenerForkMaster();

$scanerLogDir = $daemonConf->SCANERS_LOGS_PATH . '/' . $daemonConf->SCANER_NAME . ($daemonConf->SCANER_MODULE_NAME ? '/' . $daemonConf->SCANER_MODULE_NAME : '');
if (! defined('BET365FLASH_DIFFUSION_LOG_FILE_BENCHMARK')) {
    define('BET365FLASH_DIFFUSION_LOG_FILE_BENCHMARK', $scanerLogDir . '/benchmark.log', true);
}

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
    'stop'    => 'service bet365flash-live-diff-fork stop',
    'start'   => 'service bet365flash-live-diff-fork start',
    'restart' => 'service bet365flash-live-diff-fork restart',
);

$daemonConf->SCANER_ADDITIONAL_PARAMS = array(
    'some_additional_param' => ''
);

$daemon = $daemonConf->runDaemon();
$scanner->log(coloredText($daemonConf->SCANER_NAME . ' ' . $daemonConf->SCANER_MODULE_NAME . ' Daemon Started', 'green') . ' [period = ' . $period . " sec]");

$cycle_number = 1;
$period = $period * 1e6;


pcntl_signal(SIGINT, "sig_handler_child");
pcntl_signal(SIGTERM, "sig_handler_child");
pcntl_signal(SIGHUP, "sig_handler_child");
pcntl_signal(SIGCHLD, "sig_handler_child");

function sig_handler_child($sig)
{
    global $scanner;
    switch ($sig) {
        case SIGINT:
        case SIGQUIT:
        case SIGTERM:
            if (ob_get_contents()) {
                ob_end_clean();
            };

            echo date("d.m.Y H:i:s") . ' SIGTERM signal incoming. ' . coloredText('Terminating', 'red') . PHP_EOL;
            if($scanner instanceof Bet365FlashDiffusionDiffListenerForkMaster)
            {
                foreach ($scanner->getForks() as $forkNumber => $data)
                {
                    echo date("d.m.Y H:i:s") .' try stop FORK' . $forkNumber . '  pid ' . $data['pid'] . PHP_EOL;
                    posix_kill($data['pid'], 9);
                }
            }
            print_debug_backtrace();
            die();
            break;
        case SIGCHLD:
        case SIGCLD:
            if(! $scanner instanceof Bet365FlashDiffusionDiffListenerForkMaster) {
                break;
            }

            //перебираем всех запущенных детей на предмет зависания
            while ($pid = pcntl_waitpid(-1, $status, WNOHANG))
            {
                if ($pid == -1)
                {
                    $scanner->clearForks();
                    break;
                }
                else
                {
                    foreach ($scanner->getForks() as $forkNumber => $data)
                    {
                        if (! $scanner->isRun()) {
                            break;
                        }
                        if ($data['pid'] == $pid)
                        {
                            echo date("d.m.Y H:i:s") . " SIGCHLD $pid signal incoming. " . coloredText('Terminating', 'red') . PHP_EOL;
                            $scanner->clearForks($forkNumber);
                            break;
                        }
                    }
                }
            }
            break;
        default :
            echo "\n" . date("d.m.Y H:i:s") . ' incoming ' . coloredText('unnown', 'red') . ' signal : ' . $sig . "\n";
            break;
        # one branch for signal...
    }
}

register_shutdown_function('shutdownbet365flash');

function shutdownbet365flash()
{
    global $scanner;
    if(! $scanner instanceof Bet365FlashDiffusionDiffListenerForkMaster)
    {
        echo sprintf("%s FORK stop %s".PHP_EOL, date("d.m.Y H:i:s"), posix_getpid());
        return;
    }

    foreach ($scanner->getForks() as $forkNumber => $data)
    {
        echo sprintf("%s kill %s => %s".PHP_EOL, date("d.m.Y H:i:s"), $forkNumber, $data['pid']);
        posix_kill($data['pid'], 9);
    }
    echo sprintf("%s MAIN stop %s".PHP_EOL, date("d.m.Y H:i:s"), posix_getpid());
}


cli_set_process_title('php '.basename(__FILE__).' Master');

$runAgain = true;
while ($runAgain)
{
    try
    {
        if ($scanner)
        {
            foreach ($scanner->getForks() as $forkNumber => $data)
            {
                echo date("d.m.Y H:i:s") .' try stop FORK' . $forkNumber . '  pid ' . $data['pid'] . PHP_EOL;
                posix_kill($data['pid'], 9);
            }
            unset($scanner);
        }

        $scanner = new Bet365FlashDiffusionDiffListenerForkMaster();
        $scanner->setDaemonCallback('daemonCallbackMaster');
        $runAgain = false; // for fork
        $scanner->process();
    }
    catch (CanNotConnectDiffusionExeption $e)
    {
        $runAgain = true;
        echo "Master can not connect to diffusion. Try again after 1 sec\n";
        sleep(1);
    }
}

$forkNumber = $scanner->getForkNumber();
if ($forkNumber > 0) // fork runned
{
    gc_collect_cycles();
    cli_set_process_title('php ' . basename(__FILE__) . ' Fork' . $forkNumber);
    $daemon->setName($daemon->getName() . ' Fork' . $forkNumber);

    unset($scanner);
    $cycle_number = 1;
    $scanner = new Bet365FlashDiffusionDiffListenerForkWorker($forkNumber);
    $scanner->setDaemonCallback('daemonCallbackWorker');
    try {
        $scanner->process();
    } catch (CanNotConnectDiffusionExeption $e) {
        echo "Frok $forkNumber is ended because connection is lost\n";
    }
}


function daemonCallbackMaster($scanner, $started)
{
    $profiler = TimeProfiler::instance();
//    echo $profiler->getStat() . "\n";
    $profiler->clear();

    global $cycle_number, $period, $daemon;

    $cycle_report = array();
    $cycle_report['type'] = $daemon::$GLOBAL_CYCLE_REPORT_TYPE;
    $cycle_report['start_time'] = floor($started);

    foreach ($scanner->unresolved as $sportId => $mnemonics) {
        foreach ($mnemonics as $mnemonicName => $mnemonicCount) {
            $daemon->addCannotMapData('Unknown mnemonic: ' . $mnemonicName, $sportId);
        }
    }

    if ($scanner->totalEvents && $scanner->totalOutcomes) {
        $message = "Ev: {$scanner->totalEvents}, Rows: {$scanner->totalOutcomes}";
        $resultCode = $daemon::$RESULT_OK;
    } else {
        $message = "Empty";
        $resultCode = $daemon::$RESULT_EMPTY;
    }
    $message .= ", Wait: {$scanner->totalWait}, Mess: {$scanner->totalMessages}, Skip: {$scanner->totalSkipCycle}";
    $message .= ", Logs: " . round($scanner->totalLogs / 1024) . " KB";
    $scanner->totalMessages = $scanner->totalWait = $scanner->totalSkipCycle = $scanner->totalLogs = 0;

    $next_start = time() + BET365FLASH_DIFFUSION_DAEMON_PERIOD;

    $scanner->log(coloredText("Cycle: {$cycle_number}", 'light cyan') . ', ' . $message . ", " . $scanner->memUsage() . ', ' . round(microtime_float() - $started, 3) . " sec");

    $cycle_report['estimated_time'] = time() - $cycle_report['start_time'];
    $cycle_report['result_code'] = $resultCode;
    $cycle_report['next_start_time'] = $next_start;
    $cycle_report['message'] = $message;
    $daemon->send_data2scanfoll(array('cycle_report' => $cycle_report));

    $cycle_number++;
}

function daemonCallbackWorker($scanner, $started)
{
    $profiler = TimeProfiler::instance();
//    echo $profiler->getStat() . "\n";
    $profiler->clear();

    global $cycle_number, $period, $daemon;

    foreach ($scanner->unresolved as $sportId => $mnemonics) {
        foreach ($mnemonics as $mnemonicName => $mnemonicCount) {
            $daemon->addCannotMapData('Unknown mnemonic: ' . $mnemonicName, $sportId);
        }
    }

    $scanner->totalLogs += strlen($scanner->mappingLog);
    $daemon->datedLog('mapping', $scanner->mappingLog);

    if ($scanner->totalEvents && $scanner->totalOutcomes) {
        $message = "Ev: {$scanner->totalEvents}, Outc: {$scanner->totalOutcomes}";
        $resultCode = $daemon::$RESULT_OK;
    } else {
        $message = "Empty";
        $resultCode = $daemon::$RESULT_EMPTY;
    }
    $message .= ", Wait: {$scanner->totalWait}, Mess: {$scanner->totalMessages}, Skip: {$scanner->totalSkipCycle}";
    $message .= ", Logs: " . round($scanner->totalLogs / 1024) . " KB";
    $scanner->totalMessages = $scanner->totalWait = $scanner->totalSkipCycle = $scanner->totalLogs = 0;

    $next_start = time() + BET365FLASH_DIFFUSION_DAEMON_PERIOD;

    $scanner->log(coloredText("Cycle: {$cycle_number}", 'light cyan') . ', ' . $message . ", " . $scanner->memUsage() . ', ' . round(microtime_float() - $started, 3) . " sec");

    $cycle_number++;
}
