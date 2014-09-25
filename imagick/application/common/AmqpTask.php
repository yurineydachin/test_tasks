<?php

require_once APPLICATION_PATH . '/common/Task.php';

class AmqpTask extends Task
{
    public function checkParams()
    {
        parent::checkParams();

        if (! $this->message) {
            throw new Exception('No Message param');
        }
        if (! $this->amqp && ! $this->plugin->getConfigZ()->task->amqp->toArray()) {
            throw new Exception('No amqp params');
        }
    }

    protected function run()
    {
        if (is_array($this->amqp)) {
            $p = array_merge($this->plugin->getConfigZ()->task->amqp->toArray(), $this->amqp);
        } else {
            $p = $this->plugin->getConfigZ()->task->amqp->toArray();
        }
        if (! isset($p['host']) || ! isset($p['login']) || ! isset($p['password']) || ! isset($p['exchange'])) {
            throw new Exception('Not enouth amqp params');
        }

        $params = array(
            'host'     => $p['host'],
            'login'    => $p['login'],
            'password' => $p['password'],
        );
        if (isset($p['vhost'])) {
            $params['vhost'] = $p['vhost'];
        }
        if (isset($p['port'])) {
            $params['port'] = $p['port'];
        }
        $AMQPConnection = new AMQPConnection($params);
        $AMQPConnection->connect();
        $AMQPChannel = new AMQPChannel($AMQPConnection);

        $AMQPExchange = new AMQPExchange($AMQPChannel);
        $AMQPExchange->setName($p['exchange']);

        $AMQPExchange->publish(json_encode($this->message), $p['routingKey']);
    }
}
