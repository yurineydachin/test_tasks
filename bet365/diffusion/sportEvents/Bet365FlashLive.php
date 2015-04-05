<?php
/**
 * Created by Vim.
 * User: yorick
 * Date: 04.06.14
 * Time: 11:20
 */

require_once 'Bet365FlashLiveTennis.php';
require_once 'Bet365FlashLiveTableTennis.php';
require_once 'Bet365FlashLiveBasketball.php';
require_once 'Bet365FlashLiveSoccer.php';
require_once 'Bet365FlashLiveFutsal.php';
require_once 'Bet365FlashLiveHockey.php';
require_once 'Bet365FlashLiveFieldHockey.php';
require_once 'Bet365FlashLiveBaseball.php';
require_once __DIR__ . '/../../mapper.php';
require_once __DIR__ . '/../../functions.php';

/*
 * Класс для парсинга дампа и индивидуальных особенностей каждого вида спорта
 * Каждая большая ветка дерева хранит кеш предыдущего парсинга, если куст не изменялся, то используем кеш
 * Однако, все событие нельзя кешировать - таймер изменяется каждую секунду
 *
 */
class Bet365FlashLiveEvent
{
    static protected $eventTypes = array(
        SportTypes::SPORT_SOCCER         => 'Bet365FlashLiveSoccerEvent',
        SportTypes::SPORT_RUGBY          => 'Bet365FlashLiveSoccerEvent',
        SportTypes::SPORT_HANDBALL       => 'Bet365FlashLiveSoccerEvent',
        SportTypes::SPORT_FUTSAL         => 'Bet365FlashLiveFutsalEvent',
        SportTypes::SPORT_HOCKEY         => 'Bet365FlashLiveHockeyEvent',
        SportTypes::SPORT_FIELD_HOCKEY   => 'Bet365FlashLiveFieldHockeyEvent',
        SportTypes::SPORT_TENNIS         => 'Bet365FlashLiveTennisEvent',
        SportTypes::SPORT_TABLE_TENNIS   => 'Bet365FlashLiveTableTennisEvent',
        SportTypes::SPORT_VOLLEYBALL     => 'Bet365FlashLiveTableTennisEvent',
        SportTypes::SPORT_BADMINTON      => 'Bet365FlashLiveTableTennisEvent',
        SportTypes::SPORT_BEACH_VOLLEYBALL => 'Bet365FlashLiveTableTennisEvent',
        SportTypes::SPORT_BASKETBALL     => 'Bet365FlashLiveBasketballEvent',
        SportTypes::SPORT_FOOTBALL       => 'Bet365FlashLiveBasketballEvent',
        SportTypes::SPORT_BASEBALL       => 'Bet365FlashLiveBaseballEvent',
    );
    const CACHE_KEY = 'diff_bet365flash_parser_data_';
    const TIME_DELTA_DEFAULT = 2; // summer time

    protected $id;
    protected $utime;
    protected $item = array();
    public $debug = false;

    protected $sport;
    protected $sportDump;
    protected $detailDump;
    private $mapper = null;
    private $parent;
    private $lastUpdated = null;

    public function __construct(Bet365Level $sportDump, $parent)
    {
        $this->sportDump = $sportDump;
        $this->sport = $sportDump->getSport();
        $this->debug && $this->sportDump->printDumpFirstLevel();
        $this->parent = $parent;

        if (is_null($this->sport)) {
            $sportDump->printDumpParents();
            throw new Exception('Need sport!');
        }

        $this->mapper = new Bet365LiveFlashMapper();
    }

    public function __destruct()
    {
        unset($this->sportDump);
        unset($this->sportDetail);
    }

    static public function create($sportDump, $parent)
    {
        $class = 'Bet365FlashLiveEvent';
        if (isset(self::$eventTypes[$sportDump->getSport()])) {
            $class = self::$eventTypes[$sportDump->getSport()];
        }
        $obj = new $class($sportDump, $parent);
        return $obj;
    }

    public function getNumConn()
    {
        return $this->sportDump->getNumConn();
    }

    public function getDumpId()
    {
        return $this->sportDump->getVar('ID');
    }

    public function getEventKey()
    {
        return $this->sportDump->getEventKey();
    }

    public function getSportTopic()
    {
        return $this->sportDump->getTopic();
    }

    public function getDetailTopic()
    {
        return $this->detailDump->getTopic();
    }

    public function setDetailDump(Bet365Level $dump)
    {
        $this->detailDump = $dump;
        $this->debug && $this->detailDump->printDump();
        return $this;
    }

    protected function parseSport()
    {
        if (! $this->sportDump) {
            throw new Exception('Event ' . $this->id . ' does not have sportDump for parseSport');
        }
        $res = $this->sportDump->getParserResult();
        if ($res)
        {
            $this->debug && $this->log($this->id . ' FROM CACHE ' . __METHOD__. ' ' . $this->sportDump->getTopic());
            $this->item = array_merge($this->item, $res);
        }
        else
        {
            $this->debug && $this->log($this->id . ' PARSE AGAIN ' . __METHOD__. ' ' . $this->sportDump->getTopic());
            $this->debug && $this->sportDump->printDumpFirstLevel();

            $this->lastUpdated = max($this->lastUpdated, $this->sportDump->getData('lastUpdated'));

            $vars = $this->sportDump->getVars();
            $tournamentName = $vars['CT'];
            $tournamentId   = $this->getTournamentExtID($tournamentName);

            $this->utimeCacheKey = $this->getEventCacheKey($tournamentName, $vars['NA'], 'utime');
            $utime    = $this->getEventUTime();
            $this->id = $this->getEventExtID($tournamentName, $vars['NA'], $utime);

            list($home, $away) = $this->parseHomeAway($vars['NA']);
            $res = array(
                'id'         => $this->id,
                'name'       => $vars['NA'],
                'home'       => $home,
                'away'       => $away,
                'sport'      => $this->sport,
                'tournament' => $tournamentId,
                'tournament_name' => $tournamentName,
                'utime'      => $utime,
                'raw_head'   => $vars,
            );
            foreach ($res as $key => $value) {
                $this->item[$key] = $value;
            }
            $this->item['headP'] = $res['headP'] = $this->processSport();

            $this->sportDump->setParserResult($res);
        }
    }

    protected function processSport()
    {
        $res = array();
        $data = $this->item['raw_head'];

        if ($data['SS'])
        {
            if ($scoreHA = $this->parseOneScore($data['SS']))
            {
                $res['score_home'] = $scoreHA[0];
                $res['score_away'] = $scoreHA[1];
            } else {
                echo "Event {$this->id}, Error: score '{$data['SS']}' does not have d-d\n";
            }
        }
        return $res;
    }

    protected function parseDetail()
    {
        if (! $this->detailDump) {
            return;
        }

        foreach ($this->detailDump->getChildren() as $dump)
        {
            switch ($dump->getLevel())
            {
                case Bet365Level::MG:
                    $this->parseEventMG($dump);
                    break;
                case Bet365Level::SG:
                    $this->parseStat($dump);
                    break;
                case Bet365Level::ES:
                    $this->parseScore($dump);
                    break;
            }
        }
        $this->parseDetailEV();
    }

    protected function parseDetailEV()
    {
        $res = $this->detailDump->getParserResult();
        if ($res)
        {
            $this->debug && $this->log($this->id . ' FROM CACHE ' . __METHOD__. ' ' . $this->detailDump->getTopic());
            $this->item = array_merge($this->item, $res);
        }
        else
        {
            $this->debug && $this->log($this->id . ' PARSE AGAIN ' . __METHOD__. ' ' . $this->detailDump->getTopic());
            $this->debug && $this->detailDump->printDumpFirstLevel();

            $this->lastUpdated = max($this->lastUpdated, $this->detailDump->getData('lastUpdated'));

            $res = array();
            $res['raw_head2'] = $this->item['raw_head2'] = $this->detailDump->getVars();

            $profiler = TimeProfiler::instance();
            $pKey = $profiler->start(TimeProfiler::mapping);
            ob_start();
            if ($this->item['bets'])
            {
                $result = $this->mapper->setHome($this->item['home'])->setAway($this->item['away'])->setSportId($this->sport)->map($this->item['bets']);
                if (isset($result['mapped_bets'])) {
                    $res['betsMapped'] = $this->item['betsMapped'] = $result['mapped_bets'];
                }
                $res['mappingLog'] = $this->item['mappingLog'] = ob_get_contents();
            }
            ob_end_clean();
            $profiler->stop(TimeProfiler::mapping, $pKey);
            if ($this->debug && isset($res['mappingLog'])) echo $res['mappingLog'];

            $this->detailDump->setParserResult($res);
        }
    }

    protected function parseEventMG($mgDump)
    {
        $res = $mgDump->getParserResult();
        if ($res)
        {
            $this->debug && $this->log($this->id . ' FROM CACHE ' . __METHOD__. ' ' . $mgDump->getTopic());
        }
        else
        {
            $res = array(
                'bets'        => array(),
                'betsBlocked' => 0,
            );
            $this->debug && $this->log($this->id . ' PARSE AGAIN ' . __METHOD__. ' ' . $mgDump->getTopic());
            $this->debug && $mgDump->printDump();

            $home = strtoupper($this->item['home']);
            $away = strtoupper($this->item['away']);

            $mg = $mgDump->getVars();
            $mg['NA'] = isset($mg['NA']) ? $mg['NA'] : null;
            $mg['basisesIndex'] = array();
            $mg['basisesId']    = array();
            $mg['basisNA']      = null;

            foreach ($mgDump->getChildren() as $maDump)
            {
                $ma = $maDump->getVars();
                $ma['NA'] = isset($ma['NA']) ? $ma['NA'] : null;
                $ma['basisIndex'] = 0;

                $children = $maDump->getChildren();
                if (count($children) == 1 && ($child = reset($children)) && $child->getLevel() == Bet365Level::CO) {
                    $maDump = $child; // CO like MA
                }
                foreach ($maDump->getChildren() as $paDump)
                {
                    $pa = $paDump->getVars();
                    $paName = isset($pa['NA']) ? $pa['NA'] : null;

                    if (array_key_exists('OD', $pa))
                    {
                        // $basises of TOTAL, vars->HA for HANDICAP
                        $basis = null;
                        if (isset($mg['basisesId'][$pa['ID']])) {
                            $basis = $mg['basisesId'][$pa['ID']];
                        } elseif (isset($mg['basisesIndex'][$ma['basisIndex']])) {
                            $basis = $mg['basisesIndex'][$ma['basisIndex']];
                        } elseif (isset($pa['HA'])) {
                            $basis = $pa['HA'];
                        }

                        $coef = array(
                            'id'      => $mg['NA'] . '_' . $ma['NA'] . '_' . $pa['IT'],
                            'event'   => $this->id,
                            'MG_name' => $mg['NA'],
                            'MA_name' => $ma['NA'],
                            'name'    => $paName ? $paName : $mg['basisNA'],
                            'basis'   => $basis . (isset($pa['HA']) && $pa['HA'] && $pa['HA'] != $basis ? '_' . $pa['HA'] : ''),
                            'OD'      => $pa['OD'],
                        );
                        if ($pa['OD'] != '0/0' && (! isset($pa['SU']) || $pa['SU'] != 1))
                        {
                            $coef['name'] = strtoupper($coef['name']);
                            $coef['name'] = str_replace($home, 'HOME', $coef['name']);
                            $coef['name'] = str_replace($away, 'AWAY', $coef['name']);

                            if ($coef['basis'] && strpos($coef['name'], $coef['basis'])) {
                                $coef['name'] = str_replace($coef['basis'], '', $coef['name']);
                            }
                            $bet = sprintf('%s#%s#%s#%s', trim($coef['MG_name']), trim($coef['MA_name']), mb_trim($coef['name']), mb_trim($coef['basis']));

                            $bet = strtoupper($bet);
                            $bet = str_replace($home, 'HOME', $bet);
                            $bet = str_replace($away, 'AWAY', $bet);
                            $bet = str_replace(' ', '_', $bet);
                            $bet = str_replace(chr(194).chr(160), '', $bet); // utf8 space

                            $res['bets'][$bet] = parseOD($coef['OD']);
                        }
                        else
                        {
                            $res['betsBlocked']++;
                        }
                    }
                    else
                    {
                        $mg['basisesIndex'][$ma['basisIndex']] = $paName;
                        isset($pa['ID']) && $basisesId[$pa['ID']] = $paName;
                        $mg['basisNA'] = $ma['NA'];
                    }
                    $ma['basisIndex']++;
                }
            }
            $mgDump->setParserResult($res);
        }

        if (is_array($res) && isset($res['bets']) && count($res['bets'])) {
            $this->item['bets'] = array_merge($this->item['bets'], $res['bets']);
        }
        if (is_array($res) && isset($res['betsBlocked'])) {
            $this->item['betsBlocked'] += $res['betsBlocked'];
        }
    }

    protected function parseStat($sgDump)
    {
        $res = $sgDump->getParserResult();
        if ($res)
        {
            $this->debug && $this->log($this->id . ' FROM CACHE ' . __METHOD__. ' ' . $sgDump->getTopic());
            $this->item = array_merge($this->item, $res);
        }
        else
        {
            $this->debug && $this->log($this->id . ' PARSE AGAIN ' . __METHOD__. ' ' . $sgDump->getTopic());
            $this->debug && $sgDump->printDump();

            $rawStat = array();
            foreach ($sgDump->getChildren() as $stDump) {
                $rawStat[] = $stDump->getVar('LA');
            }
            $this->item['raw_stat'] = array_reverse($rawStat);
            $this->item['statP']    = $this->processStat();
            $res = array(
                'raw_stat' => $this->item['raw_stat'],
                'statP'    => $this->item['statP'],
            );

            $sgDump->setParserResult($res);
        }
    }

    protected function processStat()
    {
        return array();
    }

    protected function parseScore($esDump)
    {
        $res = $esDump->getParserResult();
        if ($res)
        {
            $this->debug && $this->log($this->id . ' FROM CACHE ' . __METHOD__ . ' ' . $esDump->getTopic());
            $this->item = array_merge($this->item, $res);
        }
        else
        {
            $this->debug && $this->log($this->id . ' PARSE AGAIN ' . __METHOD__. ' ' . $esDump->getTopic());
            $this->debug && $esDump->printDump();

            $rawScore = array();
            foreach ($esDump->getChildren() as $scDump)
            {
                $name = $scDump->getVar('NA');
                foreach ($scDump->getChildren() as $slDump) {
                    $rawScore[$name][$slDump->getVar('ID')] = $slDump->getVar('D1');
                }
            }

            $res = array(
                'raw_score' => ($this->item['raw_score'] = $rawScore),
                'scoreP'    => ($this->item['scoreP']    = $this->processScore()),
            );
            $esDump->setParserResult($res);
        }
    }

    protected function processScore()
    {
        $map = array(
            'T'               => 'score_',
            'TOTAL'           => 'score_',
            'Total'           => 'score_',
            'R'               => 'score_',
            'IGoal'           => 'score_',
            'OT'              => 'overtime_',
            'ICorner'         => 'corner_',
            //'IPenalty'        => 'penalty_',
            'IRedCard'        => 'redcard_',
            'IYellowCard'     => 'yellowcard_',
            //'ISUBSTITUTION'   => 'substitution_',
            //'H'               => 'hit_'
            //'HALF'            => 'periods' // only in basketball
        );

        $scoreClear = array();
        foreach ($this->item['raw_score'] as $name => $score)
        {
            if ((isset($score['0']) || isset($score['1'])) && (is_numeric($score['0']) || is_numeric($score['1'])))
            {
                $home = (int) $score['0'];
                $away = (int) $score['1'];

                if (array_key_exists($name, $map))
                {
                    $scoreClear[$map[$name] . 'home'] = $home;
                    $scoreClear[$map[$name] . 'away'] = $away;
                }
                elseif (is_numeric($name) || preg_match('/^\d[STNDRH]+$/i', $name))
                {
                    if (! isset($scoreClear['periods'])) {
                        $scoreClear['periods'] = array();
                    }
                    $period = (int) $name - 1;
                    $scoreClear['periods'][$period] = array(
                        'home' => $home,
                        'away' => $away,
                    );
                }
            }
        }
        return $scoreClear;
    }

    private function initialEventFields()
    {
        return array(
            'raw_score'         => array(),
            'scoreP'            => array(),
            'raw_stat'          => array(),
            'statP'             => array(),
            'raw_head'          => array(),
            'raw_head2'         => array(),
            'headP'             => array(),
            'raw_head'          => array(),
            'raw_head2'         => array(),
            'is_ended'          => null,
            'is_pause'          => null,
            'is_breaknow'       => null,
            'is_overtime'       => null,
            'is_penalty'        => null,
            'is_tiebreak'       => null,
            'score_home'        => null,
            'score_away'        => null,
            'service'           => null,
            'half'              => 1,
            'period'            => BLEvent::PERIOD_UNKNOWN,
            'periods'           => array(),
            'game_home'         => null,
            'game_away'         => null,
            'tiebreak_home'     => null,
            'tiebreak_away'     => null,
            'timer'             => null,
            'overtime_home'     => null,
            'overtime_away'     => null,
            'corner_home'       => null,
            'corner_away'       => null,
            'penalty_home'      => null,
            'penalty_away'      => null,
            'redcard_home'      => null,
            'redcard_away'      => null,
            'yellowcard_home'   => null,
            'yellowcard_away'   => null,
            'bets'              => array(),
            'betsMapped'        => array(),
            'betsBlocked'       => 0,
            'mappingLog'        => null,
            'actuality'         => $this->getTime(),
            'lastUpdated'       => null,
            'sport'             => $this->sport,
            'blocked'           => false,
        );
    }


    protected function mergeScoreCoeffs($item, $coeffSources)
    {
        if (! $coeffSources) {
            return $item;
        } elseif (count($coeffSources) == 1) {
            return array_merge($item, $coeffSources[0]);
        }

        $fields = array();
        foreach ($coeffSources as $source) {
            $fields = array_merge($fields, array_keys($source));
        }

        foreach ($fields as $field)
        {
            $fieldSources = array();
            foreach ($coeffSources as $source)
            {
                if (isset($source[$field])) {
                    $fieldSources[] = $source[$field];
                }
            }
            if (count($fieldSources) == 1) {
                $item[$field] = $fieldSources[0];
            }
            else
            {
                if ($field === 'periods')
                {
                    $allPeriods = array();
                    foreach ($fieldSources as $fieldSource) {
                        $allPeriods = array_merge($allPeriods, array_keys($fieldSource));
                    }
                    foreach ($allPeriods as $period)
                    {
                        $home = 0;
                        $away = 0;
                        foreach ($fieldSources as $fieldSource)
                        {
                            if (isset($fieldSource[$period]))
                            {
                                if ($home < $fieldSource[$period]['home']) {
                                    $home = $fieldSource[$period]['home'];
                                }
                                if ($away < $fieldSource[$period]['away']) {
                                    $away = $fieldSource[$period]['away'];
                                }
                            }
                        }
                        $item[$field][$period] = array(
                            'home' => $home,
                            'away' => $away,
                        );
                    }
                }
                else
                {
                    $fieldMax = null;
                    foreach ($fieldSources as $fieldSource)
                    {
                        if ($fieldMax < $fieldSource) {
                            $fieldMax = $fieldSource;
                        }
                    }
                    $item[$field] = $fieldMax;
                }
            }
        }

        return $item;
    }

    protected function prepareEventBeforeMerge()
    {
    }

    protected function prepareEventAfterMerge()
    {
    }

    public function prepareEvent()
    {
        $this->debug && $this->log($this->getEventKey());
        $this->item = $this->initialEventFields();
        $this->parseSport();
        $this->parseDetail();

        $this->item['lastUpdated'] = $this->lastUpdated;

        $this->prepareEventBeforeMerge();

        $coeffSources = array();
        foreach (array('headP', 'scoreP', 'statP') as $source)
        {
            if (isset($this->item[$source]) && $this->item[$source])
            {
                $coeffSources[] = $this->item[$source];
            }
        }
        $this->item = $this->mergeScoreCoeffs($this->item, $coeffSources);

        $this->prepareEventAfterMerge();

        if (isset($this->item['timer']) && $this->item['timer']) {
            $this->item['timer_h'] = floor($this->item['timer'] / 60) . ':' . ($this->item['timer'] % 60);
        }
        if (! isset($this->item['half']) && isset($this->item['periods'])) {
            $this->item['half'] = count($this->item['periods']) ? count($this->item['periods']) : 1;
        }
        if (! isset($this->item['half']) && isset($this->item['periods'])) {
            $this->item['half'] = count($this->item['periods']) ? count($this->item['periods']) : 1;
        }
        $half = $this->item['half'];

        $period = null;
        switch ($this->sport)
        {
            case SportTypes::SPORT_SOCCER:
                $periods = array(
                    1 => BLEvent::PERIOD_HT1,
                    2 => BLEvent::PERIOD_HT2,
                    3 => BLEvent::PERIOD_P3,
                    4 => BLEvent::PERIOD_P4,
                );
                if (isset($periods[$half])) {
                    $period = $periods[$half];
                } else {
                    $period = BLEvent::PERIOD_FT;
                }
                break;

            case SportTypes::SPORT_BASEBALL:
            case SportTypes::SPORT_HANDBALL:
            case SportTypes::SPORT_FOOTBALL:
            case SportTypes::SPORT_AUSSIE_RULES:
            case SportTypes::SPORT_BEACH_SOCCER:
            case SportTypes::SPORT_RUGBY:
            case SportTypes::SPORT_FUTSAL:
            case SportTypes::SPORT_TENNIS:
            case SportTypes::SPORT_TABLE_TENNIS:
            case SportTypes::SPORT_VOLLEYBALL:
            case SportTypes::SPORT_BEACH_VOLLEYBALL:
            case SportTypes::SPORT_BASKETBALL: // ?
            case SportTypes::SPORT_HOCKEY:     // ?
            case SportTypes::SPORT_BALL_HOCKEY:
                $periods = array(
                    1 => BLEvent::PERIOD_P1,
                    2 => BLEvent::PERIOD_P2,
                    3 => BLEvent::PERIOD_P3,
                    4 => BLEvent::PERIOD_P4,
                    5 => BLEvent::PERIOD_P5,
                    6 => BLEvent::PERIOD_P6,
                    7 => BLEvent::PERIOD_P7,
                    8 => BLEvent::PERIOD_P8,
                    9 => BLEvent::PERIOD_P9,
                    10 => BLEvent::PERIOD_P10,
                );
                if (isset($periods[$half])) {
                    $period = $periods[$half];
                }
                break;

            default:
                $period = BLEvent::PERIOD_FT;
                break;
        }
        $this->item['period'] = $period;
        $this->item['blocked'] = ! $this->item['bets'] && $this->item['betsBlocked'] && (! isset($this->item['is_ended']) || $this->item['is_ended'] <= 0);

        return $this->item;
    }

    private function getEventUTime()
    {
        if ($this->utime)
        {
            $res = $this->utime;
            if ($res > $this->getTime() + 900 || $res < $this->getTime() - 12*3600) {
                $this->utime = null;
            } else {
                return $res;
            }
        }
        $res = MemoryCache::get($this->utimeCacheKey);

        // если времени нет в кеше или оно не похоже на правду (далеко в прошлом или будущем), то округляем текущее время
        if (! $res || $res > $this->getTime() + 900 || $res < $this->getTime() - 12*3600)
        {
            $res = $this->roundTimeWithDelta($this->getTime());
            MemoryCache::replace($this->utimeCacheKey, $res, false, 3600 * 6);
        }
        return $this->utime = $res;
    }

    protected function getEventCacheKey($tournament, $name, $suffix)
    {
        return md5(self::CACHE_KEY . sprintf('Event_%s_%s-%s-%s', $suffix ,$this->sport, $tournament, $name));
    }

    private function getEventExtID($tournament, $name, $time)
    {
        $ret = base_convert(substr(sha1($this->sport . ' ' . $name . ' in ' . $tournament), -6), 16, 10);
        return date('ymd', $time) . $ret;
    }

    private function getTournamentExtID($name)
    {
        if (! $name) return false;
        return base_convert(substr(sha1($this->sport . $name), -6), 16, 10);
    }

    /*
     * Округлить время на $base секунд со смещением $delta
     * например, 9:58 -> 9:55, 9:59 -> 10:00, 10:00 -> 10:00, 10:01 -> 10:00, 10:02 -> 10:00, 10:03 -> 10:00, 10:04 -> 10:05
     * $delta = 0 смещения нет
     */
    private function roundTimeWithDelta($time, $base = 300, $delta = 60)
    {
        return $time - ($time + $delta) % $base + $delta;
    }

    protected function calcTimerTU($val)
    {
        if ($val)
        {
            $timeFrom = parseDate($val, self::TIME_DELTA_DEFAULT);
            if ($this->getTime() - $timeFrom < 86400) {
                return $this->getTime() - $timeFrom;
            }
        }
        return false;
    }

    protected function calcTimer($raw)
    {
        $res = 0;
        if ($raw['TT'] === '1' && ($timerTU = $this->calcTimerTU($raw['TU']))) {
            $res += $timerTU;
        }
        if ($raw['TM']) {
            $res += $raw['TM'] * 60;
        }
        if ($raw['TS']) {
            $res += $raw['TS'];
        }
        return $res;
    }

    protected function calcReverseTimer($raw, $item, $halfDuration, $overtimeHalfDuration)
    {
        $res = 0;
        if (isset($item['is_ended']) && $item['is_ended'])
        {
            $item['half']--;
        }
        elseif (isset($item['is_pause']) && $item['is_pause'])
        {
            // nothing
        }
        else
        {
            if ($raw['TT'] === '1' && ($timerTU = $this->calcTimerTU($raw['TU'])) && $timerTU > 0) {
                $res += $timerTU;
            }
        }

        if (isset($item['is_overtime']) && $item['is_overtime'])
        {
            $res += $overtimeHalfDuration * 60;
            $item['half']--;
        }

        if (isset($item['is_pause']) && $item['is_pause']) {
            $res += $halfDuration * 60 * $item['is_pause'];
        } else {
            $res += $halfDuration * 60 * $item['half'] - $raw['TM'] * 60 - $raw['TS'];
        }
        return $res;
    }

    protected function calcScorePeriods($periodStr)
    {
        $res = array();
        if ($periodStr)
        {
            if (! $res = $this->parseManyScores($periodStr)) {
                echo "Event {$this->id}, Error: periods '{$periodStr}' does not have d-d,d-d,d-d\n";
            }
        }
        return $res;
    }

    protected function calcLastPeriod($home, $away, $periods)
    {
        foreach ($periods as $score)
        {
            $home -= $score['home'];
            $away -= $score['away'];
        }
        return array('home' => $home, 'away' => $away);
    }

    protected function parseOneScore($score)
    {
        if (preg_match('/([\dA]+)-([\dA]+)/', $score, $match)) {
            return array($match[1], $match[2]);
        }
    }

    protected function parseManyScores($scores)
    {
        if (preg_match_all('/([\d]+)-([\d]+)/', $scores, $matches))
        {
            $res = array();
            foreach ($matches[1] as $key => $home)
            {
                $res[$key] = array(
                    'home' => $home,
                    'away' => $matches[2][$key],
                );
            }
            return $res;
        }
        return array();
    }

    public function getTime()
    {
        return $this->parent->getTime();
    }

    protected function parseHomeAway($name)
    {
        if (preg_match('/(.+)\s+(v|vs|@)\s+(.+)/ui', $name, $match))
        {
            return array(mb_trim($match[1]), mb_trim($match[3]));
        }
        elseif (mb_strpos($name, '  v  '))
        {
            return explode('  v  ', $name);
        }
        else
        {
            return array($name, $name);
        }
    }

    public function log($message)
    {
        echo date('d.m.Y H:i:s') . ' Ev: ' . $message . PHP_EOL;
    }
}
