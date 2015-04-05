<?php
require_once 'diffusion/connection.php';
require_once 'levels/level.php';
require_once __DIR__ . '/../TimeProfiler.php';

/*
 * Класс листенер для прослушивания диффужина
 * Чтение только дампов
 * Принцип работы:
 *   1. Подписываемся на спиков всех лайв-событий - получаем дамп
 *   2. Из дампа вытаскиваем топики всех матчей из нужных видов спорта
 *   3. Подписываемся на каждый топик матча (не более 50 одновременно) - получаем дампы
 *   4. При получении дампа отписываемся от этого топика и подписываем на другой топик из очереди
 *   5. Когда получены дампы на все события, возвращаем дампы.
 *
 * Особые условия остановки:
 *   1. Если дампы не получены в течении 10 секунд, то остановиться и отписаться от оставшихся топиков
 *   2. Если нет никаких сообщений из диффужина в течении 30 секунд - тоже остановка, реконнект
 */
class Bet365FlashDiffusionDumpListener
{
    const LEVEL_SPORT = 'Sport';
    const LEVEL_EVENT = 'Event';

    public $debug = false;

    private $connection;

    private $events = array();
    private $queue  = array();
    private $dump   = array();

    private $start       = null;

    private $sportTopics = array(
        'OV_1_1_3',    # SportTypes::SPORT_SOCCER,
                         # 2   horse racing,
                         # 3   SportTypes::SPORT_CRICKET,
                         # 4   greyhounds,
                         # 6   lotto,
        'OV_8_1_3',    # SportTypes::SPORT_RUGBY,          //Rugby Union,
        'OV_7_1_3',    # SportTypes::SPORT_GOLF,
        'OV_9_1_3',    # SportTypes::SPORT_BOX,
        'OV_10_1_3',   # SportTypes::SPORT_FORMULA1,
                         # 11  Athletics,
        'OV_12_1_3',   # SportTypes::SPORT_FOOTBALL,
        'OV_13_1_3',   # SportTypes::SPORT_TENNIS,
        'OV_14_1_3',   # SportTypes::SPORT_SNOOKER,
        'OV_15_1_3',   # SportTypes::SPORT_DARTS,
        'OV_16_1_3',   # SportTypes::SPORT_BASEBALL,
        'OV_17_1_3',   # SportTypes::SPORT_HOCKEY,
        'OV_18_1_3',   # SportTypes::SPORT_BASKETBALL,
        'OV_19_1_3',   # SportTypes::SPORT_RUGBY,          //Rugby League,
        'OV_27_1_3',   # SportTypes::SPORT_AUTO_MOTOSPORT, //Motorbikes,
        'OV_35_1_3',   # SportTypes::SPORT_BILLIARDS,      //Pool,
        'OV_36_1_3',   # SportTypes::SPORT_AUSSIE_RULES,
        'OV_38_1_3',   # SportTypes::SPORT_CYCLE_RACING,    //cycling
                         # 66  bowls
        'OV_78_1_3',   # SportTypes::SPORT_HANDBALL,
                         # 88  Trotting,
        'OV_83_1_3',   # SportTypes::SPORT_FUTSAL,
        'OV_84_1_3',   # SportTypes::SPORT_BALL_HOCKEY,
        'OV_91_1_3',   # SportTypes::SPORT_VOLLEYBALL,
        'OV_92_1_3',   # SportTypes::SPORT_TABLE_TENNIS,
        'OV_94_1_3',   # SportTypes::SPORT_BADMINTON,
        'OV_95_1_3',   # SportTypes::SPORT_BEACH_VOLLEYBALL,
        'OV_98_1_3',   # SportTypes::SPORT_CURLING,
        'OV_118_1_3',  # SportTypes::SPORT_ALPINE_SKIING,
        'OV_119_1_3',  # SportTypes::SPORT_BIATHLON,
        'OV_121_1_3',  # SportTypes::SPORT_NORDIC_COMBINED,
        'OV_122_1_3',  # SportTypes::SPORT_CROSS_COUNTRY,
        'OV_123_1_3',  # SportTypes::SPORT_SKI_JUMPING,
        'OV_124_1_3',  # SportTypes::SPORT_LUGE,
        'OV_125_1_3',  # SportTypes::SPORT_SKATING,
        'OV_127_1_3',  # SportTypes::SPORT_SKELETON,
        'OV_138_1_3',  # SportTypes::SPORT_FREESTYLE,
        'OV_139_1_3',  # SportTypes::SPORT_SNOWBOARD,
    );

    public function __construct()
    {
        if (! defined('BET365FLASH_DIFFUSION_TIMEOUT')) {
            define('BET365FLASH_DIFFUSION_TIMEOUT', 10, true);
        }
    }

    public function __destruct()
    {
        $this->getConnection()->onDisconnect();
    }

    private function clearAllSubscribe()
    {
        $this->queue  = array();
        $this->dump   = array();
        $this->events = null;
        $this->getConnection()->clearAllSubscribe();
    }

    public function getDump()
    {
        $this->getConnection()->unsubscribeAllTopics();
        $this->clearAllSubscribe();
        $this->getConnection()->subscribeInitial();

        $this->start = microtime(true);

        while (1)
        {
            $this->getConnection()->waitForMessage();

            if (is_array($this->events) && ! count($this->events))
            {
                $this->log(sprintf("Diffusion finished. %0.3f sec", microtime(true) - $this->start));
                break;
            }
            elseif ($this->start < microtime(true) - BET365FLASH_DIFFUSION_TIMEOUT)
            {
                $this->log(sprintf("Timeout. %0.3f sec", microtime(true) - $this->start));
                print_r($this->events);
                break;
            }
            elseif (! $this->getConnection()->checkLastMessage())
            {
                $this->log(sprintf("checkLastMessage. %0.3f sec", microtime(true) - $this->start));
                break;
            }
        }
        return $this->dump;
    }

    public function processMessage($message)
    {
        $messageStart = substr($message['data'], 0, 5);

        if ($messageStart == Bet365Level::MESSAGE_INITIAL . Bet365Level::MESSAGE_CHUNK_DELIM . Bet365Level::CL . Bet365Level::MESSAGE_VAR_DELIM) // F|CL;
        {
            $this->debug && $this->log('-------------------- SPORT ' . $message['topic']);

            $sportDumps = explode(Bet365Level::CL . Bet365Level::MESSAGE_VAR_DELIM, $message['data']);
            array_shift($sportDumps);

            foreach ($sportDumps as $dump)
            {
                $dump = Bet365Level::MESSAGE_INITIAL . Bet365Level::MESSAGE_CHUNK_DELIM . Bet365Level::CL . Bet365Level::MESSAGE_VAR_DELIM . $dump;

                if (preg_match('/CL;[^|]+;IT=([^;]+);/', $dump, $m) && in_array($m[1], $this->sportTopics))
                {
                    $this->dump[$sportTopic = $m[1]] = array(
                        'level'     => self::LEVEL_SPORT,
                        'actuality' => time(),
                        'message'   => $dump,
                    );

                    // SD=0;  HP=1;  ET=0;  LO=1111;  TM=45;
                    if (preg_match_all('/EV;[^|]+;SD=\d;[^|]+;CT=([^;]+);[^|]+;ID=([^;]+);[^|]+;NA=([^;]+)/', $dump, $m))
                    {
                        $this->log('Subscribe ' . $sportTopic . ': ' . count($m[2]) . ' topics: ' . implode(', ', $m[2]));
                        foreach ($m[2] as $i => $eventId)
                        {
                            $this->subscribe('6V' . $eventId, sprintf('%s|%s|%s', $sportTopic, $m[1][$i], $m[3][$i]));
                        }
                    }
                }
            }
            $this->unsubscribe($message['topic']);
        }
        elseif ($messageStart == Bet365Level::MESSAGE_INITIAL . Bet365Level::MESSAGE_CHUNK_DELIM . Bet365Level::EV  . Bet365Level::MESSAGE_VAR_DELIM) // F|EV;
        {
            $this->debug && $this->log(' -------------------- EVENT ' . $message['topic']);

            $this->dump[$message['topic']] = array(
                'level'     => self::LEVEL_EVENT,
                'actuality' => time(),
                'message'   => $message['data'],
            );
            $this->unsubscribe($message['topic']);
        }
        else
        {
            $this->debug && $this->log($message['topic'] . ' -- ' . $message['data']);
        }
    }

    private function addSubscribedQueue($topic, $name)
    {
        $this->queue[$name] = $topic;
        return true;
    }

    private function popSubscribedQueue()
    {
        $topic = reset($this->queue);
        $name = key($this->queue);
        $res = array($topic, $name);
        unset($this->queue[$name]);
        return $res;
    }

    private function subscribe($topic, $name)
    {
        if ($this->getConnection()->isTopicLimit())
        {
            return $this->addSubscribedQueue($topic, $name);
        }

        try
        {
            $this->events[$topic] = $name;
            $this->getConnection()->subscribe($topic);
            $this->debug && $this->log('Subscribe ' . $topic . ' -> ' . $name);
        }
        catch (TopicLimitException $e)
        {
            return $this->addSubscribedQueue($topic, $name);
        }
    }

    private function unsubscribe($topic)
    {

        $this->getConnection()->unsubscribe($topic);
        if (isset($this->events[$topic])) {
            $this->debug && $this->log('Unsubscribe ' . $topic . ' -> ' . $this->events[$topic]);
            unset($this->events[$topic]);
        }
        if ($this->queue) {
            list($topic, $name) = $this->popSubscribedQueue();
            return $this->subscribe($topic, $name);
        }
    }

    private function getConnection()
    {
        if (! $this->connection) {
            $this->connection = new Bet365FlashDiffusionConnection(1, array('onMessage' => array($this, 'processMessage')));
        }
        return $this->connection;
    }

    public function log($message, $error = false)
    {
        echo date('d.m.Y H:i:s') . ' Lis: ' . $message . PHP_EOL;
    }
}
