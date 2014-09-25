<?php

/*
сабж
*/
class Profiler
{
    private $logs = array(); // name => array(count_call => 0, last_call => microtime, total_time => sec);

    public function __construct()
    {
        $this->start('total');
    }

    public function start($name)
    {
        if (! isset($this->logs[$name]))
        {
            $this->logs[$name] = array('count_call' => 0, 'last_call' => null, 'total_time' => 0);
        }
        $this->logs[$name]['last_call'] = microtime(true);
    }

    public function end($name)
    {
        if (! isset($this->logs[$name]) || ! $this->logs[$name]['last_call'])
        {
            throw new Exception('No start for ' . $name);
        }
        $this->logs[$name]['total_time'] += microtime(true) - $this->logs[$name]['last_call'];
        $this->logs[$name]['count_call']++;
        $this->logs[$name]['last_call'] = null;
    }

    public function printReport()
    {
        $this->end('total');

        $res = '';
        $res .= str_pad('NAME', 30, ' ', STR_PAD_LEFT) . ' | ';
        $res .= str_pad('COUNT', 7, ' ', STR_PAD_LEFT) . ' | ';
        $res .= str_pad('TIME', 10, ' ', STR_PAD_LEFT) . ' | ';
        $res .= str_pad('AVG', 10, ' ', STR_PAD_LEFT) . "\n";
        foreach ($this->logs as $name => $stat)
        {
            $res .= str_pad($name, 30, ' ', STR_PAD_LEFT) . ' | ';
            $res .= str_pad($stat['count_call'], 7, ' ', STR_PAD_LEFT) . ' | ';
            $res .= str_pad(sprintf('%01.3f', $stat['total_time']), 10, ' ', STR_PAD_LEFT) . ' | ';
            $res .= str_pad(sprintf('%01.4f', $stat['total_time'] / $stat['count_call']), 10, ' ', STR_PAD_LEFT) . "\n";
        }
        return $res;
    }
}
