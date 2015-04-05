<?php

if (!defined('ROOT_DEFINED')) {
    define('ROOT_DEFINED', 1);
    $ROOT = __DIR__ . '/../../../';
}

date_default_timezone_set('Europe/Moscow');
ini_set('memory_limit', '2048M');
set_time_limit(60*60);

echo date("d.m.Y H:i:s\n");

include_once $ROOT . 'config/config.php';
include_once $ROOT . 'config/globals.php';
require_once 'levels/level.php';
require_once 'sportEvents/Bet365FlashLive.php';
require_once __DIR__ . '/../TimeProfiler.php';
require_once __DIR__ . '/../../BookmakerLine/BookmakerLine.class.php';


$searchDump  = 'Bet365Flash-LiveDiff-Dumps-%s-%s-%s-%s-%s-%s.log';
$searchFile  = 'Bet365Flash-LiveDiff-Messages-%s-%s-%s-%s-%s-%s.log';
$archivePath = '/var/log/betmill/scanners/Bet365Flash/LiveDiff/Curl/';
$extractTo   = '/tmp/zip/';
$filterTopic = '/OVInPlay_1_3|18824099A_1_3/';
$ignoreFields = array('actuality' => 1, 'headP / timer' => 1, 'timer' => 1, 'timer_h' => 1);
$ignoreFields = array('actuality' => 1);

$timeTo = $timeFrom = null;
foreach ($argv as $param)
{
    if (preg_match('/(s|f):(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})/', $param, $m))
    {
        if ($m[1] == 's') {
            $timeFrom = mktime($m[5], $m[6], $m[7], $m[3], $m[4], $m[2]);
        } else {
            $timeTo   = mktime($m[5], $m[6], $m[7], $m[3], $m[4], $m[2]);
        }
    }
}

if ($timeFrom && ! $timeTo) {
    $timeTo = $timeFrom + 30 * 60;
} elseif (! $timeFrom && $timeTo) {
    $timeFrom = $timeTo - 30 * 60;
} elseif (! $timeFrom && ! $timeTo) {
    $timeFrom    = mktime(date('H'), date('i') - 30, 0, date('m'), date('d'), date('Y'));
    $timeTo      = mktime(date('H'), date('i'),      0, date('m'), date('d'), date('Y'));
}

$profiler = TimeProfiler::instance();
$pTotalKey = $profiler->start(TimeProfiler::total);

if ($timeFrom > $timeTo)
{
    $tmp = $timeTo;
    $timeTo = $timeFrom;
    $timeFrom = $tmp;
}

echo sprintf("History log from %s to %s\n", date("Y-m-d H:i:s", $timeFrom), date("Y-m-d H:i:s", $timeTo));

// extracting
if (in_array('unzip', $argv))
{
    $pKey = $profiler->start('unzip');
    for ($time = $timeFrom; $time <= $timeTo; $time += 60)
    {
        $filePath = $archivePath . date('Y-m-d/H/i', $time) . '.zip';
        if (file_exists($filePath))
        {
            $zip = new ZipArchive;
            if ($zip->open($filePath) === true)
            {
                $zip->extractTo($extractTo);
                $zip->close();
                echo "Extracted $filePath\n";
            }
            else
            {
                echo "Fail extracted $filePath\n";
            }
        }
    }
    $profiler->stop('unzip', $pKey);
}

// searching
$patternFile = sprintf($searchFile, '*', '*', '*', '*', '*', '*');
//echo "Search pattern: $patternFile\n";
$files = glob($extractTo . $patternFile);
sort($files);
$toParseHistory = array();

$listener = new Bet365FlashDiffusionDiffCheckArchive();
$listener->debug = true;
$listener->filterTopic = $filterTopic;

$i = 0;
$initialDump = false;
foreach ($files as $file)
{
    $date = null;
    $time = null;
    if (preg_match('/(\d{4})-(\d{2})-(\d{2})-(\d{2})-(\d{2})-(\d{2}).log/', $file, $m)) {
        $time = mktime($m[4], $m[5], $m[6], $m[2], $m[3], $m[1]);
        $date = date('Y-m-d H:i:s', $time);
    } else {
        echo "File $file has wrong name\n";
        continue;
    }
    if ($time < $timeFrom || $time > $timeTo) {
        continue;
    }

    $dumpFile = $extractTo . sprintf($searchDump, $m[1], $m[2], $m[3], $m[4], $m[5], $m[6]);
    if (! $initialDump)
    {
        if  (file_exists($dumpFile))
        {
            $initialDump = true;
            $numConnDumps = unserialize(file_get_contents($dumpFile));
            foreach ($numConnDumps as $numConn => $dumps)
            {
                foreach ($dumps as $topic => $dump)
                {
                    $listener->processMessage($dump);
                }
            }
        }
        continue;
    }

    $pKey = $profiler->start('parseMessages');
    if (! $messages = unserialize(file_get_contents($file))) {
        echo "File $file is empty or content unserializeable\n";
        continue;
    }

    if (++$i % 10 == 0) {
        echo "$i   $date ...\n";
    }

    foreach ($messages as $message) {
        $listener->processMessage($message);
    }

    $listener->setTime($time);
    $result = $listener->daemonCycle();

    foreach ($result as $sportId => $tournaments)
    {
        foreach ($tournaments as $tournamentId => $tournament)
        {
            foreach ($tournament['events'] as $eventId => $event)
            {
                unset($event['mappingLog']);
                //unset($event['bets']);
                //unset($event['betsMapped']);
                $toParseHistory[$sportId . ' / ' . $tournamentId . ' / ' . $eventId][$date] = getFlatArray($event);
            }
        }
    }
    $profiler->stop('parseMessages', $pKey);
}

if (! $toParseHistory) {
    die("Not found matches\n");
}

// history
$pKey = $profiler->start('history');
$history = array();
foreach ($toParseHistory as $eventId => $dates)
{
    $prevData = null;
    $history[$eventId] = array();

    foreach ($dates as $date => $event)
    {
        if (! $history[$eventId])
        {
            // first event
            $history[$eventId][$date] = $event;
        }
        else
        {
            $history[$eventId][$date] = array();
            if ($diffKeys = array_diff_key($prevData, $event))
            {
                foreach ($diffKeys as $key => $value)
                {
                    $history[$eventId][$date][$key] = '---';
                }
            }
            if ($diffKeys = array_diff_key($event, $prevData))
            {
                foreach ($diffKeys as $key => $value)
                {
                    $history[$eventId][$date][$key] = $value;
                }
            }
            if ($intersectKeys = array_intersect_key($event, $prevData))
            {
                foreach ($intersectKeys as $key => $value)
                {
                    if ($value !== $prevData[$key])
                    {
                        $history[$eventId][$date][$key] = $value;
                    }
                }
            }
        }
        $prevData = $event;
    }
}
$profiler->stop('history', $pKey);

// printing
echo "\n";
$pKey = $profiler->start('print');
$profiler->stop('print', $pKey);
foreach ($history as $eventId => $diffs)
{
    echo "HISTORY of $eventId\n";
    foreach ($diffs as $date => $diff)
    {
        $diff = array_diff_key($diff, $ignoreFields);
        if (! $diff) {
            continue;
        }
        echo "    $date\n";
        foreach ($diff as $key => $value)
        {
            echo "        $key => $value\n";
        }
    }
    echo "\n";
}
$profiler->stop('print', $pKey);

$profiler->stop(TimeProfiler::total, $pTotalKey);
echo $profiler->getStat() . "\n";
$profiler->clear();


/*
 * Класс эмуляции работы листенер через логи
 */

class Bet365FlashDiffusionDiffCheckArchive
{
    private $dumpIdToEvent = array();
    private $dumps  = array();
    private $events = array();

    public $debug = false;
    public $filterTopic = null;
    private $time;

    public function __construct()
    {
        $this->dumpIdToEvent = array();
        $this->events        = array();
        $this->log('create CA');
    }

    public function setInitialDumps($numConnDumps)
    {
    }

    public function daemonCycle()
    {
        $started = microtime_float();
        $stat = array(
            'sports'      => 0,
            'tournaments' => 0,
            'events'      => 0,
            'bets'        => 0,
            'betsMapped'  => 0,
        );

        $profiler = TimeProfiler::instance();
        $pKey = $profiler->start('prepareEvents');
        $res = array();
        foreach ($this->events as $event)
        {
            $eventData = $event->prepareEvent();
            if (! isset($res[$eventData['sport']]))
            {
                $res[$eventData['sport']] = array();
                $stat['sports']++;
            }
            if (! isset($res[$eventData['sport']][$eventData['tournament']]))
            {
                $res[$eventData['sport']][$eventData['tournament']] = array(
                    'name' => $eventData['tournament_name'],
                    'events' => array(),
                );
                $stat['tournaments']++;
            }
            $res[$eventData['sport']][$eventData['tournament']]['events'][$eventData['id']] = $eventData;
            $stat['events']++;
            $stat['bets'] += count($eventData['bets']);
            $stat['betsMapped'] += count($eventData['betsMapped']);
        }
        $profiler->stop('prepareEvents', $pKey);
        echo sprintf("Sports: %d, tournaments: %d, events: %d, bets: %d, betsMapped: %d\n", $stat['sports'], $stat['tournaments'], $stat['events'], $stat['bets'], $stat['betsMapped']);

        return $res;
    }

    public function processMessage($message)
    {
        if ($this->filterTopic && ! preg_match($this->filterTopic, $message['topic'])) return;

        $messageStart = substr($message['data'], 0, 1);
        if ($messageStart == Bet365Level::MESSAGE_INITIAL)
        {
            $messageStart = substr($message['data'], 0, 5);
            if ($messageStart == Bet365Level::MESSAGE_INITIAL . Bet365Level::MESSAGE_CHUNK_DELIM . Bet365Level::CL . Bet365Level::MESSAGE_VAR_DELIM) // F|CL;
            {
                return $this->processMessageInitialSport($message);
            }
            elseif ($messageStart == Bet365Level::MESSAGE_INITIAL . Bet365Level::MESSAGE_CHUNK_DELIM . Bet365Level::EV  . Bet365Level::MESSAGE_VAR_DELIM) // F|EV;
            {
                return $this->processMessageInitialEvent($message);
            }
        }
        elseif ($messageStart == Bet365Level::MESSAGE_UPDATE || $messageStart == Bet365Level::MESSAGE_DELETE || $messageStart == Bet365Level::MESSAGE_INSERT)
        {
            $numConn = $message['numConn'];
            $firstTopic = reset($message['topics']);
            if (isset($this->dumps[$numConn][$firstTopic])) {
                $this->debug && $this->log('Conn: ' . $numConn . ' -- ' . $message['topic'] . ' -- ' . $message['data']);
                $this->dumps[$numConn][$firstTopic]->applyDiff($message);
            } else {
                $this->log(sprintf('Conn %s do not have dump with topic %s', $numConn, $message['topic']));
            }
        }
    }

    public function processMessageInitialSport($message)
    {
        $numConn = $message['numConn'];
        $this->debug && $this->log('Conn: '.$numConn.' -------------------- SPORT ' . $message['topic']);
        $dump = Bet365Level::parseMessage($message);
        $dump->setListener($this);
        $this->dumps[$numConn][$dump->getTopic()] = $dump;
        //echo "SPORT --------------------------- \n"; $this->printDump($dump);

        $events = $dump->parseEventsFromSport();
        foreach ($events as $event) {
            $this->addEvent($event);
        };
    }

    public function processMessageInitialEvent($message)
    {
        $numConn = $message['numConn'];
        $this->debug && $this->log('Conn: '.$numConn.' -------------------- EVENT ' . $message['topic']);
        $dump = Bet365Level::parseMessage($message);
        $dump->setListener($this);
        $this->dumps[$numConn][$message['topic']] = $dump;

        if ($event = $this->findEventByDump($dump)) {
            $event->setDetailDump($dump);
        }
    }

    public function addEvent(Bet365Level $sportDump)
    {
        $key = $sportDump->getEventKey();
        if (! $sportDump->getSport()) {
            return $this->log($key . ' can not add event with invalid sport');
        }

        if (! isset($this->events[$key]))
        {
            $eventObj = Bet365FlashLiveEvent::create($sportDump, $this);
            //$eventObj->debug = $this->debug;
            $this->events[$key] = $eventObj;
            $this->dumpIdToEvent[$eventObj->getDumpId()] = $eventObj;
        }
    }

    public function removeEvent(Bet365Level $detailDump)
    {
        if ($event = $this->findEventByDump($detailDump))
        {
            unset($this->events[$event->getEventKey()]);
            unset($this->dumpIdToEvent[$event->getDumpId()]);
            unset($this->dumps[$event->getNumConn()][$detailDump->getTopic()]);
            unset($event);
        }
    }

    private function findEventByDump(Bet365Level $dump)
    {
        $key = $dump->getEventKey();
        $event = null;
        if (isset($this->events[$key]))
        {
            $event = $this->events[$key];
        }
        else
        {
            $this->log('Event does not found by key ' . $key);
            $id = $dump->getVar('ID');
            if (isset($this->dumpIdToEvent[$id])) {
                $event = $this->dumpIdToEvent[$id];
            } else {
                $this->log('Event have not fount event by dumpId ' . $id);
            }
        }
        return $event;
    }

    public function getTime()
    {
        return $this->time ? $this->time : time();
    }

    public function setTime($value)
    {
        $this->time = $value;
        return $this;
    }

    public function log($message, $error = false)
    {
        echo date('d.m.Y H:i:s') . ' CA: ' . $message . PHP_EOL;
    }
}

function getFlatArray($arr, $prefix = '')
{
    $res = array();
    foreach ($arr as $key => $val)
    {
        if (is_array($val)) {
            $res = array_merge($res, getFlatArray($val, ($prefix ? $prefix . ' / ' : '') . $key));
        } else {
            $res[($prefix ? $prefix . ' / ' : '') . $key] = $val;
        }
    }
    return $res;
}
