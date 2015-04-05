<?php
/**
 * Created by Vim.
 * User: yorick
 * Date: 26.09.14
 * Time: 08:50
 */

require_once 'Bet365FlashLive.php';

class Bet365FlashLiveSoccerEvent extends Bet365FlashLiveEvent
{
    private $checkScore = array();

    /*
     sport |      event |            ID |         SS |         XP |         PI |      TU |      TM |      TS |      UC |      CP
    SOCCER | Gazovik Or | 12223395A_1_3 |        0-1 |            |            | 2014092 |      45 |       0 |         |        
    SOCCER | Sakhalin S | 12335957A_1_3 |        0-0 |            |            | 2014092 |       0 |       0 |         |        
    SOCCER | Ararat Yer | 12350663A_1_3 |        0-1 |            |            | 2014092 |       0 |       0 |    Goal |        
    SOCCER | Banants Ye | 12354002A_1_3 |        0-0 |            |            | 2014092 |       0 |       0 |         |        
    SOCCER | FC Mika II | 12356874A_1_3 |        0-0 |            |            | 2014092 |       0 |       0 |         |        
    SOCCER | Ulisses Ye | 12359081A_1_3 |            |            |            |         |       0 |       0 |         |        

  HANDBALL | Iran vs Ba |  2263280A_1_3 |      24-22 |            |            |         |      49 |      13 |         |     2nd

     sport |      event |            ID |    LM |    T2 |    DC |    SV |    VS |    TD |    VI |    MS |    C1 |    C2 |    CB
    SOCCER | Gazovik Or | 12223395A_1_3 |    42 |     2 |     1 |     1 |       |     0 |     1 | 35533 |     1 | 26493 | USVIU
    SOCCER | Sakhalin S | 12335957A_1_3 |    40 |     2 |     1 |     1 |       |     0 |     0 |     0 |     1 | 26493 |      
    SOCCER | Ararat Yer | 12350663A_1_3 |    18 |     2 |     1 |     1 |       |     0 |     0 |     0 |     1 | 26493 |      
    SOCCER | Banants Ye | 12354002A_1_3 |    18 |     2 |     1 |     1 |       |     0 |     0 |     0 |     1 | 26493 |      
    SOCCER | FC Mika II | 12356874A_1_3 |    18 |     2 |     1 |     1 |       |     0 |     0 |     0 |     1 | 26493 |      
    SOCCER | Ulisses Ye | 12359081A_1_3 |    18 |     2 |     0 |     1 |       |     0 |     0 |     0 |     1 | 26494 |      
    */

    public function prepareEventBeforeMerge()
    {
        // если голова не знает о пенальти, то счет по пенальти попадет в счет матча и будет задвоение
        if (isset($this->item['statP']['is_penalty']) && ! isset($this->item['headP']['is_penalty']) && isset($this->item['headP']['score_home']) && isset($this->item['headP']['score_away']))
        {
            $this->item['headP']['penalty_home'] = $this->item['headP']['score_home'];
            $this->item['headP']['penalty_away'] = $this->item['headP']['score_away'];
            unset($this->item['headP']['score_home']);
            unset($this->item['headP']['score_away']);
        }
    }

    public function prepareEventAfterMerge()
    {
        if ($this->item['half'] == 1 && (! isset($this->item['periods']) || ! $this->item['periods']))
        {
            $this->item['periods'][0] = array(
                'home' => $this->item['score_home'],
                'away' => $this->item['score_away'],
            );
        }
        elseif ($this->item['half'] == 2 && isset($this->item['periods'][0]) && (! isset($this->item['periods'][1]) || ! $this->item['periods'][1]))
        {
            $this->item['periods'][1] = $this->calcLastPeriod($this->item['score_home'], $this->item['score_away'], $this->item['periods']);
        }
        elseif ($this->item['half'] == 3 && $this->item['is_overtime'] && ! isset($this->item['overtime_home']) && ! isset($this->item['overtime_away']) && isset($this->item['periods'][0]) && isset($this->item['periods'][1]))
        {
            $period = $this->calcLastPeriod($this->item['score_home'], $this->item['score_away'], $this->item['periods']);
            $this->item['overtime_home'] = $period['home'];
            $this->item['overtime_away'] = $period['away'];
        }

        $this->item['score_home'] += $this->item['penalty_home'];
        $this->item['score_away'] += $this->item['penalty_away'];

        $this->item['timer'] = $this->calcTimer($this->item['raw_head']);

        $this->checkScoreToSMS($this->item);
    }

    private function checkScoreToSMS()
    {
        global $ROOT;

        $home = 0;
        $away = 0;
        $scorePeriods = array();
        foreach ($this->item['periods'] as $period)
        {
            $home += $period['home'];
            $away += $period['away'];
            $scorePeriods[] = $period['home'] . '-' . $period['away'];
        }

        if ($this->item['score_home'] !== $home + $this->item['overtime_home'] + $this->item['penalty_home'] || $this->item['score_away'] !== $away + $this->item['overtime_away'] + $this->item['penalty_away'])
        {
            if (! isset($this->checkScore[$this->id])) {
                $this->checkScore[$this->id] = -10;
            }

            $smsPath = realpath($ROOT . '../backend/scanfoll/new/smssender.php');
            $to = '79152191252, 79162502428';
            $message = sprintf('SCORE! %d-%d (%s) (%d-%d, %d-%d) %s, %s, %s at %s', $this->item['score_home'], $this->item['score_away'], implode(', ', $scorePeriods), $this->item['overtime_home'], $this->item['overtime_away'], $this->item['penalty_home'], $this->item['penalty_away'], $this->sport, $this->item['tournament_name'], $this->item['name'], date('Y-m-d H:i:s', $this->getTime()));
            $cmd = sprintf('php %s "%s" "%s"', $smsPath, $to, $message);

            if (++$this->checkScore[$this->id] % 100 === 0 && defined('SCAN_DAEMON') && SCAN_DAEMON)
            {
                echo sprintf("%s, %s times: %s\n", $this->id, $cmd, $this->checkScore[$this->id]);
                //exec($cmd);
                //require_once($ROOT.'Framework/smsProviders/sms.smsc.provider.class.php');
                //$sms = new SmsCProvider();
                //$sms->send($to, $message);
            }
            else
            {
                echo sprintf("%s, %s times: %s\n", $this->id, $message, $this->checkScore[$this->id]);
            }
        }
        else
        {
            $this->checkScore[$this->id] = -10;
        }
    }

    protected function processSport()
    {
        $data = $this->item['raw_head'];
        $res = array();
        $halfDuration = SportTypes::$HALFS_DURATION_IN_SPORT[$this->sport];

        $res['half'] = 1;
        if (strpos($data['UC'], 'Half Time, ') !== false && $data['TT'] === '0') {
            $res['is_pause'] = 1;
        }
        if ($data['TM'] == $halfDuration && $data['TS'] === '0' && $data['TT'] === '0') {
            $res['is_pause'] = 1;
        }

        if (strpos($data['UC'], 'At Half-Time') !== false)
        {
            $res['half'] = 2;
            if (preg_match('/At.Half-Time.*\s(\d+)-(\d+)\s/', $data['UC'], $match))
            {
                $res['periods'][0] = array(
                    'home' => (int) $match[1],
                    'away' => (int) $match[2],
                );
            }
        }

        if (strpos($data['UC'], 'Full Time, ') !== false && $data['TT'] === '0') {
            $res['is_pause'] = 2;
        }
        if ($data['TM'] == 2 * $halfDuration && $data['TS'] === '0' && $data['TT'] === '0') {
            $res['is_pause'] = 2;
        }

        if (preg_match('/At.Full-Time.*(\d+)-(\d+)/', $data['UC'], $m))
        {
            $res['half'] = 3;
            if ($m[1] != $m[2]) {
                $res['is_ended'] = 1;
            }
        }
        if (preg_match('/After.Extra.Time.*(\d+)-(\d+)/', $data['UC'], $m))
        {
            $res['half'] = 4;
            if ($m[1] != $m[2]) {
                $res['is_ended'] = 1;
            }
        }
        if (preg_match('/Penalty Shoot-out/', $data['UC']))
        {
            $res['half'] = 4;
            $res['is_penalty'] = 1;
        }

        if (! isset($res['is_pause']) && ! isset($res['is_ended']) && $data['TT'] === '0') {
            $res['is_breaknow'] = 1;
        }

        if ($data['SS'])
        {
            if ($scoreHA = $this->parseOneScore($data['SS']))
            {
                if (isset($res['is_penalty']))
                {
                    $res['penalty_home'] = (int) $scoreHA[0];
                    $res['penalty_away'] = (int) $scoreHA[1];
                }
                else
                {
                    $res['score_home'] = (int) $scoreHA[0];
                    $res['score_away'] = (int) $scoreHA[1];
                }
            }
            else
            {
                echo "Event {$this->id}, Error: score '{$data['SS']}' does not have d-d\n";
            }
        }

        return $res;
    }

    protected function processStat()
    {
        $res = array();

        $home = $away = 0;
        $halfDuration = SportTypes::$HALFS_DURATION_IN_SPORT[$this->sport];
        $halfCount    = SportTypes::$COUNT_HALFS_IN_SPORT[$this->sport];
        $minutesStat = array();

        $res['half'] = 1;
        $res['periods'] = array();

        foreach ($this->item['raw_stat'] as $stat)
        {
            if (preg_match('/(\d+)-(\d+).score.at.the.end.of.First.Half/', $stat, $m))
            {
                $res['periods'][0] = array(
                    'home' => (int) $m[1],
                    'away' => (int) $m[2],
                );
                $home = $res['periods'][0]['home'];
                $away = $res['periods'][0]['away'];
                $res['half'] = 2;
            }
            elseif (preg_match('/(\d+)-(\d+).score.at.the.end.of.Second.Half/', $stat, $m))
            {
                $res['half'] = 3;
                if ($m[1] >= $home && $m[2] >= $away)
                {
                    $res['periods'][1] = array(
                        'home' => (int) $m[1] - $home,
                        'away' => (int) $m[2] - $away,
                    );
                    if ($m[1] != $m[2]) {
                        $res['is_ended'] = 1;
                    }
                }
            }
            elseif ($stat === 'Extra Time')
            {
                $res['half'] = 3;
                $res['is_overtime'] = 1;
                $res['overtime_home'] = null;
                $res['overtime_away'] = null;
            }
            elseif (preg_match('/Penalty Shoot out/', $stat))
            {
                $res['half'] = 4;
                $res['is_penalty'] = 1;
            }
            elseif (preg_match('/(\d+)-(\d+).score.at.the.end.of.Extra.Time/', $stat, $m))
            {
                $res['half'] = 4;
                if ($m[1] != $m[2]) {
                    $res['is_ended'] = 1;
                }
            }
            elseif (preg_match('/^(\d+)\+?\d?\' - (.*)/', $stat, $minute))
            {
                $res['half'] = ceil($minute[1] / $halfDuration);
                $minutesStat[] = $minute;
            }
            elseif (preg_match('/^(.*) - Score \d+[stndrh]+ Penalty$/', $stat, $m))
            {
                if (! isset($res['penalty_home']) || ! isset($res['penalty_away']))
                {
                    $res['is_penalty'] = 1;
                    $res['penalty_home'] = 0;
                    $res['penalty_away'] = 0;
                }
                if ($m[1] === $this->item['home']) {
                    $res['penalty_home']++;
                } elseif ($m[1] === $this->item['away']) {
                    $res['penalty_away']++;
                }
            }
        }

        // используем поминутную статистику голов, если не смогли найти счет предыдущих периодов
        if ($this->sport == SportTypes::SPORT_SOCCER && min($res['half'], $halfCount + 1) > count($res['periods']) + 1)
        {
            $res['periods'] = array();
            foreach ($minutesStat as $minute)
            {
                $half = ceil($minute[1] / $halfDuration);
                if (preg_match('/^\d+[stndrh]+ Goal /', $minute[2]))
                {
                    if ($half > $halfCount)
                    {
                        if (! isset($res['is_overtime']))
                        {
                            $res['is_overtime'] = 1;
                            $res['overtime_home'] = null;
                            $res['overtime_away'] = null;
                        }
                    }
                    elseif (! isset($res['periods'][$half - 1]))
                    {
                        $res['periods'][$half - 1] = array(
                            'home' => 0,
                            'away' => 0,
                        );
                    }
                    if (strpos($minute[2], $this->item['home']))
                    {
                        if ($half > $halfCount) {
                            $res['overtime_home']++;
                        } else {
                            $res['periods'][$half - 1]['home']++;
                        }
                    }
                    elseif (strpos($minute[2], $this->item['away']))
                    {
                        if ($half > $halfCount) {
                            $res['overtime_away']++;
                        } else {
                            $res['periods'][$half - 1]['away']++;
                        }
                    }
                }
            }
        }

        return $res;
    }
}
