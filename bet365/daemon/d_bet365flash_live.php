<?php
	if (!defined('ROOT_DEFINED')) {
		define('ROOT_DEFINED', 1);
		$ROOT = __DIR__ . '/../../../';
	}

	date_default_timezone_set('Europe/Moscow');

	require_once $ROOT . 'config/config.php';
	require_once $ROOT . 'scaners/scandaemon/scandaemon.php';
	require_once $ROOT . 'scaners/bet365flash/scanner.php';
	require_once $ROOT . 'scaners/bet365flash/TimeProfiler.php';

	$period = isset($_SERVER['argv'][2]) ? $_SERVER['argv'][2] : 3;
	$scanner = new Bet365FlashScanerLive();

	$daemonConf = scanDaemon_config::getInstance();
	$daemonConf->SCANFOL_ENABLED = true;
	$daemonConf->SCANFOL_PORT = 10000;
	$daemonConf->SCANFOL_IP = '127.0.0.1';
	$daemonConf->SCANERS_LOGS_PATH = '/var/log/betmill/scanners';

	$daemonConf->SCANER_NAME = 'Bet365Flash';
	$daemonConf->SCANER_MODULE_NAME = 'Live';
	$daemonConf->SCANER_PERIOD = $period;

	$daemonConf->SCANER_LINKS = array(
    'site_url'         => '5.226.180.9',
    //'proxy'            => BET365FLASH_LIVE_PROXY,
		'test_url'         => SITE_ROOT_URL . 'scaners/bet365flash/',
		'betradar-result'  => SITE_ROOT_URL . 'scaners/betradar-lineview.php?line=' . $scanner->SCANNER_MEMCACHE_NAME,
		'memcached-result' => SITE_ROOT_URL . 'scaners/BookmakerLine/BookmakerLine2json.php?line=' . $scanner->SCANNER_MEMCACHE_NAME,
	);

	$daemonConf->SCANER_LOGS = array(
		'mapping' => $daemonConf->SCANERS_LOGS_PATH . '/' . $daemonConf->SCANER_NAME . ($daemonConf->SCANER_MODULE_NAME ? '/' . $daemonConf->SCANER_MODULE_NAME : '') . '/Mapping',
		'curl'    => $daemonConf->SCANERS_LOGS_PATH . '/' . $daemonConf->SCANER_NAME . ($daemonConf->SCANER_MODULE_NAME ? '/' . $daemonConf->SCANER_MODULE_NAME : '') . '/Curl'
	);

	$daemonConf->SCANER_CONTROL_COMMANDS = array(
		'stop'    => 'service bet365flash-live stop',
		'start'   => 'service bet365flash-live start',
		'restart' => 'service bet365flash-live restart',
	);

	$daemonConf->SCANER_ADDITIONAL_PARAMS = array(
		'some_additional_param' => ''
	);

	$daemon = $daemonConf->runDaemon();

	$scanner->log(coloredText($daemonConf->SCANER_NAME . ' ' . $daemonConf->SCANER_MODULE_NAME . ' Daemon Started', 'green') . ' [period = ' . $period . " sec]");

	$cycle_number = 1;
	$period = $period * 1e6;

	while (true) {

		$started = microtime_float();
		$scanner->log(coloredText("Cycle {$cycle_number} Started", 'light cyan'));

		managePythonDaemon();

		$cycle_report = array();
		$cycle_report['type'] = $daemon::$GLOBAL_CYCLE_REPORT_TYPE;
		$cycle_report['start_time'] = time();

		$profiler = TimeProfiler::instance();
		$pKey = $profiler->start(TimeProfiler::total);

		$scanner->process();
		foreach ($scanner->getWarnings() as $message) {
			echo sprintf("Warning: %s\n", $message);
		}

		$profiler->stop(TimeProfiler::total, $pKey);
		//echo 'profiler: ' . $profiler->getLogTimesString() . "\n";
		echo $profiler->getStat() . "\n";
		$profiler->clear();

		foreach ($scanner->unresolved as $sportId => $mnemonics) {
			foreach ($mnemonics as $mnemonicName => $mnemonicCount) {
				$daemon->addCannotMapData('Unknown mnemonic: ' . $mnemonicName, $sportId);
			}
		}

		if ($scanner->totalEvents && $scanner->totalOutcomes) {
			$message = "Events: {$scanner->totalEvents}, Outcomes: {$scanner->totalOutcomes}";
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
		//$daemon->datedLog('curl', $scanner->curlPages);

		$scanner->log($message);
		$scanner->log("Next Run: " . date('d.m.Y H:i:s', $next_start) . ", " . $scanner->memUsage());
		$scanner->log("Cycle Finished [" . round(microtime_float() - $started, 2) . " sec]");

		$cycle_report['estimated_time'] = time() - $cycle_report['start_time'];
		$cycle_report['result_code'] = $resultCode;
		$cycle_report['next_start_time'] = $next_start;
		$cycle_report['message'] = $message;
		$daemon->send_data2scanfoll(array('cycle_report' => $cycle_report));

		usleep($to_sleep);
		$cycle_number++;
	}

function managePythonDaemon($kill = false)
{
    global $scanner;
    if ($kill)
    {
        $scanner->log('Shutdown Python Daemon');
        shell_exec("pkill -f 'bet365scan.py'");
        sleep(2);
        $procCount = trim(shell_exec('pgrep -fl "bet365scan.py"|wc -l'));
        if (empty($procCount))
        {
            $scanner->log('Success');
            return true;
        }
        else
        {
            $scanner->log('Failed');
            return false;
        }
    }
    else
    {
        $procCount = trim(shell_exec('pgrep -fl "bet365scan.py" | grep -i python | wc -l'));

        if (empty($procCount))
        {
            if (defined('SCAN_DAEMON') && SCAN_DAEMON == 1) {
                chdir(__DIR__ . '/..');
            }
            $pid = trim(shell_exec('python python/bet365scan.py start'));
            $scanner->log('Start Python Daemon [' . $pid . ']');
        }
        else
        {
            $scanner->log('Python Daemon Working, ' . $procCount . ' processes');
        }
    }
    return true;
}
