<?php
/**
 * Created by Vim.
 * User: yorick
 * Date: 19.11.14
 * Time: 12:10
 */

require_once 'Bet365FlashLive.php';

class Bet365FlashLiveFutsalEvent extends Bet365FlashLiveEvent
{
    /*
     sport |      event |            ID |         SS |         XP |         PI |      TU |      TM |      TS |      UC |      CP |      TD
    FUTSAL | Spartak Mo |  141119557487 |        1-1 |            |            | 2014111 |      11 |      38 |         |         |       1


     sport |      event |            ID |    LM |    T2 |    DC |    SV |    VS |    VI |    MS |    C1 |    C2 |    CB |    ED
    FUTSAL | Spartak Mo |  141119557487 |    16 |     5 |     1 |       |       |     0 |     0 |     1 | 14824 |       |      
    */

    public function prepareEventAfterMerge()
    {
        $halfDuration = SportTypes::$HALFS_DURATION_IN_SPORT[$this->sport];
        $this->item['timer'] = $this->calcReverseTimer($this->item['raw_head'], $this->item, $halfDuration, 5);
        $this->item['timer_h'] = floor($this->item['timer'] / 60) . ':' . ($this->item['timer'] % 60);

        if (! isset($this->item['periods']) || ! $this->item['periods'])
        {
            $this->item['periods'][] = array(
                'home' => $this->item['score_home'],
                'away' => $this->item['score_away'],
            );
        }

        if ($this->item['half'] - 1 == count($this->item['periods']) && $this->item['half'] <= SportTypes::$COUNT_HALFS_IN_SPORT[$this->sport])
        {
            $this->item['periods'][$this->item['half'] - 1] = $this->calcLastPeriod($this->item['score_home'], $this->item['score_away'], $this->item['periods']);
        }
    }

    protected function processSport()
    {
        $res = array();
        $event = $this->item['id'];
        $data = $this->item['raw_head'];
        $halfDuration = SportTypes::$HALFS_DURATION_IN_SPORT[$this->sport];

        if ($data['UC'] == 'Time out') {
            $res['is_breaknow'] = 1;
        }

        $res['half'] = 1;
        if ($data['TM'] == $halfDuration && $data['TS'] === '0' && $data['TT'] === '0') {
            $res['is_pause'] = 1;
        }

        if (strpos($data['UC'], 'At Half-Time') !== false)
        {
            $res['half'] = 2;
            $res['is_pause'] = 1;
            if (preg_match('/At.Half-Time.*\s(\d+)-(\d+)\s/', $data['UC'], $match))
            {
                $res['periods'][0] = array(
                    'home' => (int) $match[1],
                    'away' => (int) $match[2],
                );
            }
        }

        if ($data['TM'] == 2 * $halfDuration && $data['TS'] === '0' && $data['TT'] === '0') {
            $res['is_pause'] = 2;
        }
        if (strpos($data['UC'], 'At Full-Time') !== false)
        {
            $res['half'] = 3;
            $res['is_pause'] = 2;
        }

        if ($data['SS'])
        {
            if ($scoreHA = $this->parseOneScore($data['SS']))
            {
                $res['score_home'] = (int) $scoreHA[0];
                $res['score_away'] = (int) $scoreHA[1];

                if ($res['half'] == 1)
                {
                    $res['periods'][0] = array(
                        'home' => $res['score_home'],
                        'away' => $res['score_away'],
                    );
                }
                elseif ($res['half'] == 2 && isset($res['periods'][0]) && ! isset($res['periods'][1]))
                {
                    $res['periods'][1] = $this->calcLastPeriod($res['score_home'], $res['score_away'], $res['periods']);
                }
                elseif ($res['half'] == 3 && ! isset($res['periods'][0]) && isset($res['periods'][1]))
                {
                    $res['periods'][0] = $this->calcLastPeriod($res['score_home'], $res['score_away'], $res['periods']);
                }
            }
            else
            {
                echo "Event $event, Error: score '{$data['SS']}' does not have d-d\n";
            }
        }

        return $res;
    }

    protected function processStat()
    {
        $res = array();

        $home = $away = 0;
        foreach ($this->item['raw_stat'] as $stat)
        {
            if (preg_match('/(\d+)-(\d+).score.at.the.end.of.First.Half/', $stat, $match))
            {
                $res['periods'][0] = array(
                    'home' => (int) $match[1],
                    'away' => (int) $match[2],
                );
                $home = $res['periods'][0]['home'];
                $away = $res['periods'][0]['away'];
                $res['half'] = 2;
            }
            elseif (preg_match('/(\d+)-(\d+).score.at.the.end.of.Second.Half/', $stat, $match))
            {
                $res['periods'][1] = array(
                    'home' => (int) $match[1] - $home,
                    'away' => (int) $match[2] - $away,
                );
                $res['half'] = 3;
            }
        }

        return $res;
    }
}
