<?php
/**
 * Created by Vim.
 * User: yorick
 * Date: 26.09.14
 * Time: 08:50
 */

require_once 'Bet365FlashLive.php';

class Bet365FlashLiveHockeyEvent extends Bet365FlashLiveEvent
{
    /*
     sport |      event |            ID |         SS |         XP |         PI |      TU |      TM |      TS |      UC |      CP
    HOCKEY | HC Molot P | 12347206A_1_3 |        3-2 |            |            | 2014092 |       0 |       0 | At end  |      P2
    HOCKEY | HC Molot P | 12347206A_1_3 |        4-3 |            |            | 2014092 |       3 |      25 | Powerpl |      P3

     sport |      event |            ID |    LM |    T2 |    DC |    SV |    VS |    TD |    VI |    MS |    C1 |    C2 |    CB
    HOCKEY | HC Molot P | 12347206A_1_3 |    42 |     5 |     1 |       |       |     0 |     0 |     0 |     1 |  2390 |      
    HOCKEY | HC Molot P | 12347206A_1_3 |    41 |     5 |     1 |       |       |     0 |     0 |     0 |     1 |  2390 |      
    */

    /*
        if ($res['half'] !== count($res['periods']))
        {
            $res['periods'][$res['half'] - 1] = $this->calcLastPeriod($res['score_home'], $res['score_away'], $res['periods']);
        }
    */

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

        $halfs = array(
            'P1' => 1,
            'P2' => 2,
            'P3' => 3,
            'OT' => 4,
        );
        if (isset($halfs[$data['CP']])) {
            $half = $halfs[$data['CP']];
        } else {
            $half = 1;
        }
        $res['half'] = $half;

        if (preg_match('/^At end of/', $data['UC']))
        {
            $res['is_pause'] = 1;
            $res['is_breaknow'] = 1;
        }

        if ($data['CP'] == 'OT')
        {
            $res['is_overtime'] = 1;
            $res['timer'] = 300 - $data['TM'] * 60 - $data['TS'];
            $res['timer'] += SportTypes::$HALFS_DURATION_IN_SPORT[$this->sport] * SportTypes::$COUNT_HALFS_IN_SPORT[$this->sport]  * 60;
        }
        else
        {
            $res['timer'] = $this->calcTimer($data);
            $res['timer'] += SportTypes::$HALFS_DURATION_IN_SPORT[$this->sport] * ($res['half'] - 1) * 60;
        }

        return $res;
    }

    protected function processStat()
    {
        $res = array();
        foreach ($this->item['raw_stat'] as $stat)
        {
            if (preg_match('/(\d+)-(\d+).score.at.the.end.of.1st.Period/', $stat, $match))
            {
                $res['periods'][0] = array(
                    'home' => $match[1],
                    'away' => $match[2],
                );
                $res['half'] = 2;
            }
            elseif (preg_match('/(\d+)-(\d+).score.at.the.end.of.2nd.Period/', $stat, $match))
            {
                $res['periods'][1] = array(
                    'home' => $match[1],
                    'away' => $match[2],
                );
                $res['half'] = 3;
            }
            elseif (preg_match('/(\d+)-(\d+).score.at.the.end.of.3rd.Period/', $stat, $match))
            {
                $res['periods'][2] = array(
                    'home' => $match[1],
                    'away' => $match[2],
                );
                $res['half'] = 4;
            }
        }

        return $res;
    }
}
