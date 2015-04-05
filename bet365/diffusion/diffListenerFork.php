<?php
require_once 'connection.php';
require_once 'levels/level.php';
require_once 'sportEvents/Bet365FlashLive.php';
require_once __DIR__ . '/../TimeProfiler.php';
require_once __DIR__ . '/../../BookmakerLine/BookmakerLine.class.php';

/*
 * Класс листенер для прослушивания диффужина (схема Мастер-Воркер-форк или М-Ф)
 * Чтение дампов и диффов
 * Принцип работы мастера:
 *   1. М: Подписываемся на спиок всех лайв-событий - получаем дамп
 *   2. М: Из дампа вытаскиваем элементы EV всех матчей из нужных видов спорта
 *   3. М: Назначаем матчи каждого воркеру (не более 45) через кеш-конфиг. Если воркеров недостаточно - создаём новые форки
 *   4. М: При срабатывании таймера DAEMON_PERIOD собираем со всех воркеров статистику для логов и сканфола (кол-во матчей и исходов)
 *   5. М: При срабатывании таймера SAVE_LOGS сохраняем все сообщения из диффужина в логи сканера
 *   6. М: Новые матчи назначаются мастером воркеру по порядку и только после этого принимаются в обработку воркером
 *   7. М: Если получили сиглан о смерти воркера, а у него были активные матчи, то раскидываем его матчи по живым воркеа или создаём новый, если все заняты

 * Принцип работы форка/воркера:
 *   1. Ф: Подписываемся на спиок всех лайв-событий - получаем дамп
 *   2. Ф: Из кеша получаем список всех доступных для данного воркера матчей
 *   3. Ф: Из дампа вытаскиваем элементы EV всех матчей из нужных видов спорта, создаём объекты матчей
 *   4. Ф: Подписываемся на каждый топик матча (коннект всего один, а значит не более 50 матчей)
 *   5. Ф: При получении дампа матча сохраняем его в объект матча
 *   6. Ф: При срабатывании таймера DAEMON_PERIOD собираем со всех матчей данные (состояние, исходы) и сохраняем BookmakerLine
 *   7. Ф: При срабатывании таймера SAVE_LOGS сохраняем все сообщения из диффужина в логи сканера
 *   8. Ф: Если у воркера кончились все события, то он умирает за ненадобностью.
 *
 */
abstract class Bet365FlashDiffusionDiffListenerForkBase
{
    protected $connection;
    protected $forkNumber = 0;
    protected $run = true;

    protected $counter = array(
        'sport' => 0,
        'event' => 0,
        'diff'  => 0,
        'wait'  => 0,
    );

    protected $dumpIdToEvent = array();
    protected $dumps  = array();
    protected $events = array();
    protected $eventsPrevState = array();
    protected $curlLogs  = array();

    protected $nextCycle;
    protected $lastSaveLogs;
    protected $lastSaveDumps;
    protected $daemonCallback;

    public $SCANNER_MEMCACHE_NAME = 'bet365flash_data_live_diff_fork';

    public $debug = false;
    public
        $totalEvents    = 0,
        $totalOutcomes  = 0,
        $totalWait      = 0,
        $totalMessages  = 0,
        $totalSkipCycle = 0,
        $totalLogs      = 0,
        $unresolved = array(),
        $mappingLog = '';

    public function __construct()
    {
        if (! defined('BET365FLASH_DIFFUSION_DAEMON_PERIOD')) {
            define('BET365FLASH_DIFFUSION_DAEMON_PERIOD', 0.5, true);
        }
        if (! defined('BET365FLASH_DIFFUSION_SAVE_LOGS')) {
            define('BET365FLASH_DIFFUSION_SAVE_LOGS', 0, true);
        }
        if (! defined('BET365FLASH_DIFFUSION_SAVE_DUMPS')) {
            define('BET365FLASH_DIFFUSION_SAVE_DUMPS', 599, true);
        }
        if (! defined('BET365FLASH_DIFFUSION_FORK_EMPTY_TIMEOUT')) {
            define('BET365FLASH_DIFFUSION_FORK_EMPTY_TIMEOUT', 59, true);
        }
        if (! defined('BET365FLASH_DIFFUSION_WAIT_TIMEOUT')) {
            define('BET365FLASH_DIFFUSION_WAIT_TIMEOUT', 10000, true);
        }
        if (! defined('BET365FLASH_DIFFUSION_SUBSCRIBED_MAX')) {
            define('BET365FLASH_DIFFUSION_SUBSCRIBED_MAX', 50 , true);
        }
        if (! defined('BET365FLASH_DIFFUSION_FORK_MATCHES')) {
            define('BET365FLASH_DIFFUSION_FORK_MATCHES', BET365FLASH_DIFFUSION_SUBSCRIBED_MAX - 5 , true);
        }

        $this->dumpIdToEvent = array();
        $this->events        = array();
        $this->eventsPrevState = array();
        $this->log('create diffListener');
    }

    public function isRun()
    {
        return $this->run;
    }

    public function getForkNumber()
    {
        return $this->forkNumber;
    }

    public function __destruct()
    {
        $this->connection && $this->connection->onDisconnect();
    }

    public function setDaemonCallback($func)
    {
        $this->daemonCallback = $func;
    }

    public function process()
    {
        $conn = $this->createConnection();
        $conn->subscribeInitial();
        $this->nextCycle       = microtime(true) + BET365FLASH_DIFFUSION_DAEMON_PERIOD;
        $this->lastSaveLogs    = time();
        $this->lastSaveDumps   = time();

        while ($this->run)
        {
            $profiler = TimeProfiler::instance();
            $pKey = $profiler->start('waitForMessage');
            $this->counter['wait']++;
            $this->connection->waitForMessage();
            if (! $this->run) {
                return; // fork
            }
            if (! $this->connection->checkLastMessage())
            {
                $this->clearEvents();
            }
            $profiler->stop('waitForMessage', $pKey);

            if (defined('SCAN_DAEMON') && SCAN_DAEMON && ! $this->debug)
            {
                if ($this->lastSaveLogs < time() - BET365FLASH_DIFFUSION_SAVE_LOGS)
                {
                    $pKey = $profiler->start('saveMessages');
                    $this->lastSaveLogs = time();
                    $this->totalLogs += strlen(serialize($this->curlLogs));
                    scanDaemon::getInstance()->datedLog('curl', $this->curlLogs, 'Messages');
                    $this->curlLogs = array();
                    $profiler->stop('saveMessages', $pKey);
                }
                if ($this->lastSaveDumps < time() - BET365FLASH_DIFFUSION_SAVE_DUMPS)
                {
                    $pKey = $profiler->start('saveDumps');
                    $this->log('Saving dumps ...');
                    $this->lastSaveDumps = time();
                    $flatDumps = array();
                    foreach ($this->dumps as $topic => $dump)
                    {
                        $flatDumps[$topic] = array(
                            'topic'   => $topic,
                            'topics'  => array($topic),
                            'numConn' => $this->forkNumber,
                            'data'    => Bet365Level::MESSAGE_INITIAL . Bet365Level::MESSAGE_CHUNK_DELIM . $dump->getDumpFlat(),
                        );
                    }
                    $this->totalLogs += strlen(serialize(array($this->forkNumber => $flatDumps)));
                    scanDaemon::getInstance()->datedLog('curl', array($this->forkNumber => $flatDumps), 'Dumps');
                    $profiler->stop('saveDumps', $pKey);
                }
            }
            if ($this->nextCycle < microtime(true))
            {
                $this->totalWait      += $this->counter['wait'];
                $this->totalMessages  += $this->counter['sport'] + $this->counter['event'] + $this->counter['diff'];
                $isQueueFull = $this->counter[$this->forkNumber] >= $this->counter['wait'] * 0.9;
                $stat = '';
                foreach ($this->counter as $name => $value)
                {
                    $stat .= ($stat ? ',  ' : '') . $name . ': ' . $value;
                    $this->counter[$name] = 0;
                }

                if (! $isQueueFull)
                {
                    //$this->log('Normal queue: ' . $stat);
                    $start = microtime(true);
                    $this->daemonCycle();
                }
                else
                {
                    $this->totalSkipCycle++;
                    $this->log('Full queue: ' . $stat);
                }
                $this->nextCycle = microtime(true) + BET365FLASH_DIFFUSION_DAEMON_PERIOD;
            }
        }
    }

    public function processMessage($message)
    {
        $numConn = $message['numConn'];
        $this->counter[$numConn]++; 
        $profiler = TimeProfiler::instance();
        $pKey = $profiler->start('processMessage' . $numConn);
        $this->curlLogs[] = $message;
        $messageStart = substr($message['data'], 0, 1);
        if ($messageStart == Bet365Level::MESSAGE_INITIAL)
        {
            $messageStart = substr($message['data'], 0, 5);
            if ($messageStart == Bet365Level::MESSAGE_INITIAL . Bet365Level::MESSAGE_CHUNK_DELIM . Bet365Level::CL . Bet365Level::MESSAGE_VAR_DELIM) // F|CL;
            {
                $this->counter['sport']++; 
                $this->processMessageInitialSport($message);
            }
            elseif ($messageStart == Bet365Level::MESSAGE_INITIAL . Bet365Level::MESSAGE_CHUNK_DELIM . Bet365Level::EV  . Bet365Level::MESSAGE_VAR_DELIM) // F|EV;
            {
                $this->counter['event']++; 
                $this->processMessageInitialEvent($message);
            }
        }
        elseif ($messageStart == Bet365Level::MESSAGE_UPDATE || $messageStart == Bet365Level::MESSAGE_DELETE || $messageStart == Bet365Level::MESSAGE_INSERT)
        {
            $this->counter['diff']++; 
            $firstTopic = reset($message['topics']);
            if (isset($this->dumps[$firstTopic])) {
                $this->debug && $this->log('Conn: ' . $numConn . ' - ' . $message['topic'] . ' -- ' . $message['data']);
                $this->dumps[$firstTopic]->applyDiff($message);
            } else {
                $this->log(sprintf('Conn %s do not have dump with topic %s', $numConn, $message['topic']));
            }
        }
        $profiler->stop('processMessage' . $numConn, $pKey);
    }

    public function processMessageInitialSport($message)
    {
        $numConn = $message['numConn'];
        $this->debug && $this->log('Conn: '.$numConn.' -------------------- SPORT ' . $message['topic']);
        $dump = Bet365Level::parseMessage($message);
        $dump->setListener($this);
        $this->dumps[$dump->getTopic()] = $dump;

        $events = $dump->parseEventsFromSport();
        foreach ($events as $event)
        {
            if ($this->run) {
                $this->addEvent($event);
            } else {
                return; // fork
            }
        }
    }

    abstract public function processMessageInitialEvent($message);

    abstract protected function daemonCycle();

    abstract public function addEvent(Bet365Level $sportDump);

    abstract public function removeEvent(Bet365Level $detailDump);

    abstract protected function clearEvents();

    protected function subscribe($topic)
    {
        $this->connection->subscribe($topic);
    }

    protected function unsubscribe($topic)
    {
        $this->connection->unsubscribe($topic);
    }

    private function createConnection()
    {
        $conn = new Bet365FlashDiffusionConnection($this->forkNumber, array('onMessage' => array($this, 'processMessage')));
        $this->counter[$this->forkNumber] = 0; 
        return $this->connection = $conn;
    }

    public function getTime()
    {
        return time();
    }

    public function log($message, $error = false)
    {
        echo date('d.m.Y H:i:s') . ($this->forkNumber ? ' Fork' . $this->forkNumber . ':  ': ' Master: ') . $message . PHP_EOL;
    }

    public function memUsage()
    {
        return "Memory: " . number_format(round(memory_get_usage() / 1024 / 1024, 2)) . " MB";
    }
}

// -----------------------------------MASTER---------------------------------------------

class Bet365FlashDiffusionDiffListenerForkMaster extends Bet365FlashDiffusionDiffListenerForkBase
{
    private $forks = array();

    public function getForks()
    {
        return $this->forks;
    }

    public function clearForks($forkNumber = null)
    {
        if ($forkNumber)
        {
            if (isset($this->forks[$forkNumber]))
            {
                $events = $this->forks[$forkNumber]['events'];
                unset($this->forks[$forkNumber]);
                foreach ($events as $key => $v)
                {
                    unset($this->events[$key]);
                    unset($this->eventsPrevState[$key]);
                    if ($this->run) {
                        $this->addEventKey($key);
                    } else {
                        return; // fork
                    }
                }
            }
            else
            {
                $this->log('Fork does not exist: ' . $forkNumber);
            }
        }
        elseif (is_null($forkNumber) && $this->forks)
        {
            // kill all ???
        }
    }

    protected function daemonCycle()
    {
        $started = microtime_float();
        $save = array();

        $this->totalEvents = $this->totalOutcomes = 0;

        foreach ($this->forks as $forkNumber => $fork)
        {
            $cacheKey = $this->SCANNER_MEMCACHE_NAME . '_' . $forkNumber;
            $save[] = $cacheKey;
            $forkStat = $this->getForkStat($cacheKey);
            $this->totalEvents   += $forkStat['events'];
            $this->totalOutcomes += $forkStat['betsMapped'];
        }
        MemoryCache::replace($this->SCANNER_MEMCACHE_NAME, serialize($save));

        if ($this->daemonCallback) {
            call_user_func($this->daemonCallback, $this, $started);
        }
    }

    private function getForkStat($cacheKey)
    {
        $stat = array(
            'sports'      => 0,
            'tournaments' => 0,
            'events'      => 0,
            'bets'        => 0,
            'betsMapped'  => 0,
        );
        $line = unserialize(MemoryCache::get($cacheKey));
        if (is_object($line))
        {
            $stat['tournaments'] = count($line->tournaments);
            foreach ($line->events as $key)
            {
                $event = unserialize(MemoryCache::get($key));
                if (is_object($event))
                {
                    $stat['events']++;
                    $stat['bets']       += count($event->row);
                    $stat['betsMapped'] += count($event->row);
                }
            }
        }
        else
        {
            $this->log('Wrong fork result: ' . $cacheKey);
        }
        return $stat;
    }

    public function processMessageInitialEvent($message)
    {
        throw new Exception('Method does not support');
    }

    public function addEvent(Bet365Level $sportDump)
    {
        $key = $sportDump->getEventKey();
        if (! $sportDump->getSport()) {
            return $this->log($key . ' can not add event with invalid sport');
        }
        $this->addEventKey($key);
    }

    private function addEventKey($key)
    {
        if (! isset($this->events[$key]))
        {
            $this->log('addEvent ' . $key);
            if (! $forkNumber = $this->getVacantFork()) // create fork
            {
                $forkNumber = $this->getNewForkNumber();
                $pid = pcntl_fork();
                if ($pid == 0)
                {
                    $this->run        = false;
                    $this->forks      = array();
                    $this->events     = array();
                    $this->eventsPrevState = array();
                    $this->forkNumber = $forkNumber;
                    $this->connection->number = $forkNumber;
                    $this->connection->onDisconnect();
                    MemoryCache::close();
                    //unset($this->connection);
                    return;
                }
                else
                {
                    $this->connection->unsubscribeAllTopics();
                    $this->connection->onDisconnect();
                    $this->connection->subscribeInitial();
                    MemoryCache::close();
                    $this->log("fork $forkNumber => $pid");
                    $this->forks[$forkNumber] = array('pid' => $pid, 'started' => time(), 'events' => array());
                }
            }

            $this->events[$key] = $forkNumber;
            $this->eventsPrevState[$key] = array();
            $this->forks[$forkNumber]['events'][$key] = $forkNumber;
            $this->saveForkConfig();
        }
    }

    private function saveForkConfig()
    {
        MemoryCache::replace($this->SCANNER_MEMCACHE_NAME . '_config', serialize($this->forks));
    }

    private function getVacantFork()
    {
        foreach ($this->forks as $forkNumber => $fork)
        {
            if (count($fork['events']) < BET365FLASH_DIFFUSION_FORK_MATCHES) {
                return $forkNumber;
            }
        }
        return null;
    }

    private function getNewForkNumber()
    {
        $variants = array();
        for ($i = 1; $i <= count($this->forks) + 1; $i++) {
            $variants[] = $i;
        }
        return min(array_diff($variants, array_keys($this->forks)));
    }

    public function removeEventSport(Bet365Level $dump)
    {
        return $this->removeEvent($dump);
    }

    public function removeEvent(Bet365Level $dump)
    {
        $key = $dump->getEventKey();
        if (isset($this->events[$key]))
        {
            $this->log('removeEvent ' . $key);
            $forkNumber = $this->events[$key];
            unset($this->events[$key]);
            unset($this->eventsPrevState[$key]);
            unset($this->forks[$forkNumber]['events'][$key]);
            $this->saveForkConfig();
        }
        else
        {
            $this->log('Try to remove unknown event: ' . $key);
        }
    }

    protected function clearEvents()
    {
        // nothing
    }
}

// -----------------------------------WORKER---------------------------------------------

class Bet365FlashDiffusionDiffListenerForkWorker extends Bet365FlashDiffusionDiffListenerForkBase
{
    private $freeEvents = array();
    private $lastEventsSaved;

    public function __construct($forkNumber)
    {
        $this->forkNumber = $forkNumber;
        $this->lastEventsSaved = time();
        parent::__construct();
    }

    public function processMessageInitialEvent($message)
    {
        $numConn = $message['numConn'];
        $this->debug && $this->log('Conn: '.$numConn.' -------------------- EVENT ' . $message['topic']);
        $dump = Bet365Level::parseMessage($message);
        $dump->setListener($this);
        $this->dumps[$message['topic']] = $dump;

        if ($event = $this->findEventByDump($dump)) {
            $event->setDetailDump($dump);
        }
    }

    protected function daemonCycle()
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
        foreach ($this->events as $key => $event)
        {
            $eventData = $event->prepareEvent();
            $this->compareEventState($key, $eventData);
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
        //$this->log(sprintf("Sports: %d, tournaments: %d, events: %d, bets: %d, betsMapped: %d", $stat['sports'], $stat['tournaments'], $stat['events'], $stat['bets'], $stat['betsMapped']));

        $this->makeBL($res);

        if ($this->daemonCallback) {
            call_user_func($this->daemonCallback, $this, $started);
        }

        $this->checkFreeEvents();

        if ($this->events)
        {
            $this->lastEventsSaved = time();
        }
        elseif ($this->lastEventsSaved < time() - BET365FLASH_DIFFUSION_FORK_EMPTY_TIMEOUT)
        {
            $this->log('Empty data timeout. Exit');
            $this->run = false;
        }
    }

    private function makeBL($data)
    {
        $profiler = TimeProfiler::instance();
        $pKey = $profiler->start('makeBL');
        $mtime = microtime(true);

        $this->totalEvents = $this->totalOutcomes = 0;

        $BL = new BookmakerLine(Lines::LINE_BET365);
        $BL->setLogOutcomesEnabled(false);
        $BL->setScanMode(0);

        if (! $this->debug) {
            ob_start();
        }
        foreach ($data as $sportId => $tournaments)
        {
            $BL->selectSport($sportId);
            foreach ($tournaments as $tournamentId => $tournament)
            {
                $BLTour = $BL->selectTournament($tournament['name']);
                $BLTour->setExternalId($tournamentId);
                $BLTour->setName($tournament['name'], 'ru');
                $BLTour->setName($tournament['name'], 'en');

                if (! empty($tournament['events']))
                {
                    foreach ($tournament['events'] as $eventId => $event)
                    {
                        $homeName = $event['home'];
                        $awayName = $event['away'];

                        $this->log("[$sportId][" . date('d.m.Y H:i:s', $event['utime']) . "] " . $event['id'] . ' ' . $homeName . ' vs ' . $awayName . "  tn:" . $tournament['name']);
                        $printEvent = $event;
                        unset($printEvent['bets']);
                        unset($printEvent['betsMapped']);
                        unset($printEvent['mappingLog']);
                        print_r($printEvent);

                        //Исходы
                        $outcomes = array('coeff_SCORE_IS_LIVE' => 1);

                        $BLEvent = $BL->selectEvent($homeName, $awayName, $event['id']);

                        //Localize
                        $BLEvent->setHomeName($homeName, 'ru');
                        $BLEvent->setAwayName($awayName, 'ru');

                        $BLEvent->setHomeName($homeName, 'en');
                        $BLEvent->setAwayName($awayName, 'en');

                        //Live?
                        $BLEvent->setIsLive(true);

                        $mapScore = array(
                            'half'             => 'coeff_SCORE_HALF',
                            'service'          => 'coeff_SCORE_CURRENT_SERVICE',
                            //'is_ended'         => 'coeff_SCORE_ENDED',
                            'score_home'       => 'coeff_SCORE_HOME',
                            'score_away'       => 'coeff_SCORE_AWAY',
                            'game_home'        => 'coeff_SCORE_HOME_GAME',
                            'game_away'        => 'coeff_SCORE_AWAY_GAME',
                            'is_overtime'      => 'coeff_SCORE_OVERTIME',
                            'overtime_home'    => 'coeff_SCORE_OVERTIME_HOME',
                            'overtime_away'    => 'coeff_SCORE_OVERTIME_AWAY',
                            'is_breaknow'      => 'coeff_SCORE_IS_BREAKNOW',
                            'is_pause'         => 'coeff_SCORE_PAUSE',
                            'timer'            => 'current_second',
                            'corner_home'      => 'coeff_SCORE_HOME_CORNER',
                            'corner_away'      => 'coeff_SCORE_AWAY_CORNER',
                            'is_penalty'       => 'coeff_SCORE_PENALTY',
                            'penalty_home'     => 'coeff_SCORE_HOME_PENALTY',
                            'penalty_away'     => 'coeff_SCORE_AWAY_PENALTY',
                            'redcard_home'     => 'coeff_SCORE_HOME_RED_CARDS',
                            'redcard_away'     => 'coeff_SCORE_AWAY_RED_CARDS',
                            'yellowcard_home'  => 'coeff_SCORE_HOME_YELLOW_CARDS',
                            'yellowcard_away'  => 'coeff_SCORE_AWAY_YELLOW_CARDS',
                            'is_tiebreak'      => 'coeff_SCORE_TIEBREAK',
                            'tiebreak_home'    => 'coeff_SCORE_HOME_TIEBREAK',
                            'tiebreak_away'    => 'coeff_SCORE_AWAY_TIEBREAK',
                            'lastUpdated'      => 'coeff_SCORE_TIMER_SECONDS',
                        );
                        foreach ($mapScore as $field => $coeff)
                        {
                            if (isset($event[$field])) {
                                $outcomes[$coeff] = $event[$field];
                            }
                        }

                        if (isset($event['is_ended']) && $event['is_ended'] > 0) {
                            $outcomes['coeff_SCORE_ENDED'] = 1;
                        }

                        $BLEvent->setIsModifying($event['blocked']);

                        if (isset($event['periods']))
                        {
                            foreach ($event['periods'] as $p => $score)
                            {
                                $p_suffix = ($p == 0) ? '' : $p + 1;
                                $outcomes['coeff_SCORE_HOME_HT' . $p_suffix] = intVal($score['home']);
                                $outcomes['coeff_SCORE_AWAY_HT' . $p_suffix] = intVal($score['away']);
                            }
                        }

                        //Live?
                        $BLEvent->setIsLive(true);
                        if (isset($event['period'])) {
                            $BLEvent->setPeriod($event['period']);
                        }

                        //Unix Time
                        if ($event['utime']) {
                            $BLEvent->setDateTimeUT($event['utime']);
                        }

                        $this->log("scoreCoeffs: " . print_r($outcomes, true));
                        if (! $this->debug) echo $event['mappingLog'];

                        if (! empty($event['betsMapped']))
                        {
                            $outcomes = array_merge($outcomes, $event['betsMapped']);
                        }

                        //Записываем исходы в BL
                        if (count($outcomes))
                        {
                            $BLEvent->addOutComes($outcomes);
                            $this->totalOutcomes += count($outcomes);
                        }

                        $this->totalEvents ++;

                        if (defined('SCAN_DAEMON') && SCAN_DAEMON == 1) {
                            $BLEvent->setActuality(scanDaemon::getDataActuality(true, $event['actuality']));
                        }
                    }
                }
            }
        }

        $this->unresolved = array();
        if (! $this->debug) {
            $this->mappingLog = ob_get_contents();
            ob_end_clean();
        }
        $profiler->stop('makeBL', $pKey);

        $pKey = $profiler->start('importMem');
        $BL->importLineToMemcache($this->SCANNER_MEMCACHE_NAME . '_' . $this->forkNumber);
        $profiler->stop('importMem', $pKey);
    }

    private function checkFreeEvents()
    {
        $profiler = TimeProfiler::instance();
        $pKey = $profiler->start('checkFreeEvents');
        $config = $this->loadForkConfig();
        if ($config && isset($config['events']) && is_array($config['events']) && $config['events'])
        {
            $newDumps = array_intersect_key($this->freeEvents, $config['events']);
            if ($newDumps)
            {
                foreach ($newDumps as $key => $dump)
                {
                    unset($this->freeEvents[$key]);
                    $this->addEventAndSubscribe($dump);
                }
            }
        }
        $profiler->stop('checkFreeEvents', $pKey);
    }

    private function loadForkConfig()
    {
        $allConfig = unserialize(MemoryCache::get($this->SCANNER_MEMCACHE_NAME . '_config'));
        return isset($allConfig[$this->forkNumber]) ? $allConfig[$this->forkNumber] : null;
    }

    public function addEvent(Bet365Level $sportDump)
    {
        $key = $sportDump->getEventKey();
        if (! $sportDump->getSport()) {
            return $this->log($key . ' can not add event with invalid sport');
        }
        $this->freeEvents[$key] = $sportDump;
    }

    private function addEventAndSubscribe(Bet365Level $sportDump)
    {
        $key = $sportDump->getEventKey();
        $this->log('addEvent ' . $key);
        if (! isset($this->events[$key]))
        {
            if (! $this->connection->isTopicLimit())
            {
                $eventObj = Bet365FlashLiveEvent::create($sportDump, $this);
                $eventObj->debug = $this->debug;
                $this->events[$key] = $eventObj;
                $this->subscribe('6V' . $eventObj->getDumpId());
                $this->dumpIdToEvent[$eventObj->getDumpId()] = $eventObj;
            }
            else
            {
                $this->log('Topic limit! Can not subscribe event ' . $key);
                $this->freeEvents[$key] = $sportDump;
            }
        }
    }

    public function removeEventSport(Bet365Level $sportDump)
    {
        unset($this->freeEvents[$sportDump->getEventKey()]);
    }

    public function removeEvent(Bet365Level $detailDump)
    {
        $this->log('removeEvent ' . $detailDump->getEventKey());
        if ($event = $this->findEventByDump($detailDump))
        {
            unset($this->events[$event->getEventKey()]);
            unset($this->dumpIdToEvent[$event->getDumpId()]);
            unset($this->dumps[$detailDump->getTopic()]);
            try {
                $this->unsubscribe($detailDump->getTopic());
            } catch (Exception $e) {
                $this->log($e);
            }
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

    protected function clearEvents()
    {
        foreach ($this->events as $key => $event)
        {
            unset($this->dumpIdToEvent[$event->getDumpId()]);
            unset($this->events[$key]);
        }
        foreach ($this->freeEvents as $key => $dump)
        {
            unset($this->freeEvents[$key]);
        }
        $this->dumps         = array();
        $this->dumpIdToEvent = array();
        $this->events        = array();
        $this->freeEvents    = array();
    }

    private function compareEventState($key, $newState)
    {
        if (isset($this->eventsPrevState[$key]) && $this->eventsPrevState[$key])
        {
            $prevState = $this->eventsPrevState[$key];
            $title  = sprintf('Event %s v %s classification %s', $newState['home'], $newState['away'], $newState['sport']);
            $this->compareAndlogScore($title . " new score ", $prevState, $newState, 'score_home', 'score_away');
            foreach ($newState['periods'] as $period => $newScore)
            {
                if (isset($prevState['periods'][$period]))
                {
                    $prevScore = $prevState['periods'][$period];
                    $this->compareAndlogScore($title . " new score period_$period ", $prevScore, $newScore, 'home', 'away');
                }
            }
            $this->compareAndlogBlocking($title . " blocking changed to ", $prevState, $newState, 'blocked');
        }
        $this->eventsPrevState[$key] = $newState;
    }

    private function compareAndlogScore($title, $prev, $new, $fHome, $fAway)
    {
        if ($prev[$fHome] != $new[$fHome] || $prev[$fAway] != $new[$fAway])
        {
            $message = $title . sprintf("%d-%d. Was %d-%d", $new[$fHome], $new[$fAway], $prev[$fHome], $prev[$fAway]);
            $this->logBenchmark($message);
        }
    }

    private function compareAndlogBlocking($title, $prev, $new, $field)
    {
        if ($prev[$field] != $new[$field])
        {
            $message = $title . ($new[$field] ? '1' : '0');
            $this->logBenchmark($message);
        }
    }

    private function logBenchmark($message)
    {
        $this->log($message);
        if (defined('BET365FLASH_DIFFUSION_LOG_FILE_BENCHMARK')) {
            file_put_contents(BET365FLASH_DIFFUSION_LOG_FILE_BENCHMARK, sprintf("%s: %s\n", date('M d H:i:s'), $message), FILE_APPEND);
        }
    }
}
