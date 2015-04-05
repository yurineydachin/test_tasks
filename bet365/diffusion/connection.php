<?php
/*
 * Класс для прослушивания диффужина
 * Умеет устанавливать соединение с диффужином, подписаться на топики, получать сообщения и отдавать их обработку
 * Также может ловить странное поведение: если не было сообщений больше 30 секунд - реконнект
 */
class Bet365FlashDiffusionConnection
{
    private $connection;
    public  $number;

    private $subscribedCountAll = 0;
    private $subscribedTopics   = array();
    private $lastMessage;
    private $lastSubscribeInitial;

    private $callbackOnMessage;

    const INITIAL_TOPIC = 'OVInPlay_1_3';
    const TOPIC_DELIMITER = '/';

    public function __construct($number = 1, $callbacks)
    {
        $this->number = $number;
        if (! defined('BET365FLASH_DIFFUSION_HOST')) {
            define('BET365FLASH_DIFFUSION_HOST', '5.226.180.9' , true);
        }
        if (! defined('BET365FLASH_DIFFUSION_PORT')) {
            define('BET365FLASH_DIFFUSION_PORT', 843 , true);
        }
        if (! defined('BET365FLASH_DIFFUSION_RECONNECT')) {
            define('BET365FLASH_DIFFUSION_RECONNECT', 30, true);
        }
        if (! defined('BET365FLASH_DIFFUSION_SUBSCRIBED_MAX')) {
            define('BET365FLASH_DIFFUSION_SUBSCRIBED_MAX', 50 , true);
        }
        if (! defined('BET365FLASH_DIFFUSION_WAIT_TIMEOUT')) {
            define('BET365FLASH_DIFFUSION_WAIT_TIMEOUT', 500000 , true);
        }

        if (isset($callbacks['onMessage'])) {
            $this->callbackOnMessage = $callbacks['onMessage'];
        } else {
            throw new DiffConnectionException('Need onMessage callback!');
        }
        $this->lastMessage = microtime(true);
        $this->log('Create connection');
    }

    public function __destruct()
    {
        $this->onDisconnect();
    }

    public function waitForMessage()
    {
        diff_wait_for_message($this->getConnection(), 0, BET365FLASH_DIFFUSION_WAIT_TIMEOUT);
    }

    public function onMessage($message)
    {
        try
        {
            if ($message['encoding'] == DIFFUSION_COMPRESSED_ENCODING) {
                $message['data'] = gzuncompress($message['data']);
            }
            $this->lastMessage = microtime(true);
            $message['numConn'] = $this->number;
            $message['topics'] = explode(self::TOPIC_DELIMITER, $message['topic']);
            call_user_func($this->callbackOnMessage, $message);
        }
        catch (DiffConnectionException $e)
        {
            $this->log(sprintf("Error decoding/processing message. %s: %s", get_class($e), $e->getMessage()));
        }
    }

    public function onDisconnect()
    {
        $this->log('Got disconnect');
        if ($this->connection && diff_connection_alive($this->connection))
        {
            diff_disconnect($this->connection);
            $this->log('Disconnected');
        }
        $this->clearAllSubscribe();
        $this->connection = null;
    }

    public function clearAllSubscribe()
    {
        $this->subscribedCountAll = 0;
        $this->subscribedTopics   = array();
    }

    public function checkLastMessage()
    {
        if ($this->lastMessage < microtime(true) - BET365FLASH_DIFFUSION_RECONNECT)
        {
            $this->lastMessage = microtime(true);
            $this->unsubscribeAllTopics();
            $this->log(sprintf("Reconnect. %0.3f sec", microtime(true) - $this->lastMessage));
            $this->onDisconnect();
            $this->subscribeInitial();
            return false;
        }
        return true;
    }

    public function subscribeInitial()
    {
        $this->subscribe(self::INITIAL_TOPIC);
        $this->lastSubscribeInitial = microtime(true);
    }

    public function getTimeSubscribeInitial()
    {
        return $this->lastSubscribeInitial;
    }

    public function isTopicLimit()
    {
        return $this->countVacantSubscribed() <= 0;
    }

    public function countVacantSubscribed()
    {
        return BET365FLASH_DIFFUSION_SUBSCRIBED_MAX - count($this->subscribedTopics);
    }

    public function subscribe($topic)
    {
        if ($this->isTopicLimit()) {
            throw new TopicLimitExeption();
        }

        if (isset($this->subscribedTopics[$topic])) {
            throw new DiffConnectionException("Subscribe: already subscribed to topic " . $topic);
        }

        if (! diff_subscribe($this->getConnection(), $topic . '//')) {
            throw new DiffConnectionException("Error subscribing to topic " . $topic);
        }

        $this->subscribedTopics[$topic] = $topic;
        $this->subscribedCountAll++;
    }

    public function unsubscribe($topic)
    {
        if (! isset($this->subscribedTopics[$topic])) {
            throw new DiffConnectionException("Unsubscribe: not subscribed to topic " . $topic);
        }

        if (! diff_unsubscribe($this->getConnection(), $topic)) {
            throw new DiffConnectionException("Error unsubscribing from topic " . $topic);
        }

        unset($this->subscribedTopics[$topic]);
    }

    public function unsubscribeAllTopics()
    {
        if (! $this->subscribedTopics) {
            return;
        }
        $this->log('Unsubscribing all sports/events');
        foreach ($this->subscribedTopics as $topic)
        {
            try {
                $this->unsubscribe($topic);
            } catch (DiffConnectionException $e) {
                $this->log(sprintf('Error unsubscribe_all. %s: %s', get_class($e), $e->getMessage()));
            }
        }
    }

    private function getConnection()
    {
        if ($this->connection && diff_connection_alive($this->connection)) {
            return $this->connection;
        }

        $this->connection = diff_connect(BET365FLASH_DIFFUSION_HOST, BET365FLASH_DIFFUSION_PORT);

        if (! $this->connection) {
            throw new CanNotConnectDiffusionExeption();
        }

        diff_register_callback($this->connection, DIFFUSION_CALLBACK_ON_DISCONNECT, array($this, 'onDisconnect'));
        $bindOnMessage = array(
            DIFFUSION_CALLBACK_ON_INITIAL_LOAD,
            DIFFUSION_CALLBACK_ON_DELTA,
            DIFFUSION_CALLBACK_ON_FETCH_REPLY,
            DIFFUSION_CALLBACK_ON_ACK,
            DIFFUSION_CALLBACK_ON_UNHANDLED_MESSAGE,
        );

        foreach ($bindOnMessage as $callbackType) {
            diff_register_callback($this->connection, $callbackType, array($this, 'onMessage'));
        }

        if (! diff_connection_alive($this->connection)) {
            throw new DiffConnectionException('Connection have not alived yet');
        }

        return $this->connection;
    }

    public function log($message, $error = false)
    {
        echo date('d.m.Y H:i:s') . ' Conn' . $this->number . ': ' . $message . PHP_EOL;
    }
}

class DiffConnectionException extends Exception {}

class TopicLimitExeption extends DiffConnectionException
{
    public function __construct($code = null, $e = null)
    {
        parent::__construct("Limit subscribed topics " . BET365FLASH_DIFFUSION_SUBSCRIBED_MAX, $code, $e);
    }
}

class CanNotConnectDiffusionExeption extends DiffConnectionException
{
    public function __construct($code = null, $e = null)
    {
        parent::__construct(sprintf("Can't connect to %s:%d", BET365FLASH_DIFFUSION_HOST, BET365FLASH_DIFFUSION_PORT), $code, $e);
    }
}
