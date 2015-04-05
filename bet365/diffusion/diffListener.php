<?php
require_once 'connection.php';
require_once 'levels/level.php';
require_once 'sportEvents/Bet365FlashLive.php';
require_once __DIR__ . '/../TimeProfiler.php';
require_once __DIR__ . '/../../BookmakerLine/BookmakerLine.class.php';

//TODO: понять почему перестало работать отписывание от топика в коннекшине
//TODO: искать способы оптимизации: 
//         1. возможно течет память из-за большого кол-ва объектов
//         2. более умное кеширование (чтобы не всякое изменение полностью удаляло кеш куста или целиком маппинг)
//         3. найти оптимальное время цикла сканера
//         4. разбить сканер на форки, чтобы у каждого процесса было не более одного коннекта. Это позволит гарантировать минимальное время цикла и равномерную нагрузку на все ядра

/*
 * Класс листенер для прослушивания диффужина
 * Чтение дампов и диффов
 * Принцип работы:
 *   1. Подписываемся на спиок всех лайв-событий - получаем дамп
 *   2. Из дампа вытаскиваем элементы EV всех матчей из нужных видов спорта, создаём объекты матчей
 *   3. Подписываемся на каждый топик матча (не более 50 на один коннект)
 *   4. При получении дампа матча сохраняем его в объект матча
 *   5. При срабатывании таймера DAEMON_PERIOD собираем со всех матчей данные (состояние, исходы) и сохраняем BookmakerLine + отрабатываем коллбек демона (отчет в сканфол)
 *   6. При срабатывании таймера SAVE_LOGS сохраняем все сообщения из диффужина в логи сканера
 *
 */
class Bet365FlashDiffusionDiffListener
{
    private $connections = array();
    private $counter = array(
        'sport' => 0,
        'event' => 0,
        'diff'  => 0,
        'wait'  => 0,
    );

    private $dumpIdToEvent = array();
    private $dumps  = array();
    private $events = array();
    private $curlLogs  = array();

    private $nextCycle;
    private $lastSaveLogs;
    private $lastSaveDumps;
    private $daemonCallback;

    public $SCANNER_MEMCACHE_NAME = 'bet365flash_data_live_diff';

    public $debug = false;
    public
        $totalEvents = null,
        $totalOutcomes = null,
        $totalConnections = null,
        $unresolved = array(),
        $mappingLog = '',
        $curlPages = array();

    public function __construct()
    {
        if (! defined('BET365FLASH_DIFFUSION_RELOAD_ALL')) {
            define('BET365FLASH_DIFFUSION_RELOAD_ALL', 300, true);
        }
        if (! defined('BET365FLASH_DIFFUSION_DAEMON_PERIOD')) {
            define('BET365FLASH_DIFFUSION_DAEMON_PERIOD', 0.5, true);
        }
        if (! defined('BET365FLASH_DIFFUSION_SAVE_LOGS')) {
            define('BET365FLASH_DIFFUSION_SAVE_LOGS', 0, true);
        }
        if (! defined('BET365FLASH_DIFFUSION_SAVE_DUMPS')) {
            define('BET365FLASH_DIFFUSION_SAVE_DUMPS', 599, true);
        }
        if (! defined('BET365FLASH_DIFFUSION_WAIT_TIMEOUT')) {
            define('BET365FLASH_DIFFUSION_WAIT_TIMEOUT', 1000, true);
        }

        $this->dumpIdToEvent = array();
        $this->events        = array();
        $this->log('create diffListener');
    }

    public function __destruct()
    {
        foreach ($this->connections as $conn) {
            $conn->onDisconnect();
        }
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

        while (1)
        {
            $profiler = TimeProfiler::instance();
            $pKey = $profiler->start('waitForMessage');
            $this->counter['wait']++;
            foreach ($this->connections as $numConn => $conn)
            {
                $conn->waitForMessage();
                if (! $conn->checkLastMessage())
                {
                    $this->clearEventsInConn($numConn);
                }
            }
            $profiler->stop('waitForMessage', $pKey);

            if (defined('SCAN_DAEMON') && SCAN_DAEMON && ! $this->debug)
            {
                if ($this->lastSaveLogs < time() - BET365FLASH_DIFFUSION_SAVE_LOGS)
                {
                    $pKey = $profiler->start('saveMessages');
                    $this->lastSaveLogs = time();
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
                    foreach ($this->dumps as $numConn => $dumps)
                    {
                        foreach ($dumps as $topic => $dump)
                        {
                            $flatDumps[$numConn][$topic] = array(
                                'topic'   => $topic,
                                'topics'  => array($topic),
                                'numConn' => $numConn,
                                'data'    => Bet365Level::MESSAGE_INITIAL . Bet365Level::MESSAGE_CHUNK_DELIM . $dump->getDumpFlat(),
                            );
                        }
                    }
                    scanDaemon::getInstance()->datedLog('curl', $flatDumps, 'Dumps');
                    $profiler->stop('saveDumps', $pKey);
                }
            }
            if ($this->nextCycle < microtime(true))
            {
                $isQueueFull = false;
                foreach ($this->connections as $numConn => $conn)
                {
                    if ($this->counter[$numConn] >= $this->counter['wait'] * 0.9) { // очередь не разгребли
                        $isQueueFull = true;
                    }
                }
                $stat = '';
                foreach ($this->counter as $name => $value)
                {
                    $stat .= ($stat ? ',  ' : '') . $name . ': ' . $value;
                    $this->counter[$name] = 0;
                }

                if (! $isQueueFull)
                {
                    $this->log('Daemon cycle: ' . $stat);
                    $start = microtime(true);
                    $this->daemonCycle();
                }
                else
                {
                    $this->log('Skip daemon cycle: ' . $stat);
                }
                $this->nextCycle = microtime(true) + BET365FLASH_DIFFUSION_DAEMON_PERIOD;
            }
        }
    }

    private function daemonCycle()
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

        $this->makeBL($res);

        if ($this->daemonCallback) {
            call_user_func($this->daemonCallback, $this, $started);
        }
    }

    private function makeBL($data)
    {
        $profiler = TimeProfiler::instance();
        $pKey = $profiler->start('makeBL');
        $mtime = microtime(true);

        $this->totalEvents = $this->totalOutcomes = 0;
        $this->totalConnections = count($this->connections);

        $BL = new BookmakerLine(Lines::LINE_BET365);
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

                        $BLEvent->setIsModifying(! count($event['bets']) && (! isset($event['is_ended']) || $event['is_ended'] <= 0));

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
        $BL->importLineToMemcache($this->SCANNER_MEMCACHE_NAME);
        $profiler->stop('importMem', $pKey);
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
            if (isset($this->dumps[$numConn][$firstTopic])) {
                $this->debug && $this->log('Conn: ' . $numConn . ' - ' . $message['topic'] . ' -- ' . $message['data']);
                $this->dumps[$numConn][$firstTopic]->applyDiff($message);
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
            $numConn = $sportDump->getNumConn();
            if (! $this->connections[$numConn]->isTopicLimit())
            {
                $eventObj = Bet365FlashLiveEvent::create($sportDump, $this);
                $eventObj->debug = $this->debug;
                $this->events[$key] = $eventObj;
                $this->subscribe('6V' . $eventObj->getDumpId(), $numConn);
                $this->dumpIdToEvent[$eventObj->getDumpId()] = $eventObj;
            }
            else
            {
                $countVacant = 0;
                foreach ($this->connections as $conn) {
                    $countVacant += $conn->countVacantSubscribed();
                }

                if (! $countVacant)
                {
                    $conn = $this->createConnection();
                    $conn->subscribeInitial();
                }
            }
        }
    }

    public function removeEventSport(Bet365Level $sportDump)
    {
    }

    public function removeEvent(Bet365Level $detailDump)
    {
        if ($event = $this->findEventByDump($detailDump))
        {
            unset($this->events[$event->getEventKey()]);
            unset($this->dumpIdToEvent[$event->getDumpId()]);
            unset($this->dumps[$event->getNumConn()][$detailDump->getTopic()]);
            try {
                $this->unsubscribe($detailDump->getTopic(), $detailDump->getNumConn());
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

    private function clearEventsInConn($numConn)
    {
        foreach ($this->events as $key => $event)
        {
            if ($event->getNumConn() === $numConn)
            {
                unset($this->dumpIdToEvent[$event->getDumpId()]);
                unset($this->events[$key]);
                $this->dumps[$numConn] = array();
            }
        }
    }

    private function subscribe($topic, $numConn)
    {
        $this->connections[$numConn]->subscribe($topic);
    }

    private function unsubscribe($topic, $numConn)
    {
        $this->connections[$numConn]->unsubscribe($topic);
    }

    private function createConnection()
    {
        $numConn = count($this->connections);
        $conn = new Bet365FlashDiffusionConnection($numConn, array('onMessage' => array($this, 'processMessage')));
        $this->counter[$numConn] = 0; 
        return $this->connections[$numConn] = $conn;
    }

    public function getTime()
    {
        return time();
    }

    public function log($message, $error = false)
    {
        echo date('d.m.Y H:i:s') . ' Lis: ' . $message . PHP_EOL;
    }

    public function memUsage()
    {
        return "Memory usage: " . number_format(round(memory_get_usage() / 1024 / 1024, 2)) . " MB";
    }
}
