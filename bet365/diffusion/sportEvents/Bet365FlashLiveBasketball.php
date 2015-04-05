<?php
/**
 * Created by Vim.
 * User: yorick
 * Date: 26.09.14
 * Time: 08:50
 */

require_once 'Bet365FlashLive.php';

class Bet365FlashLiveBasketballEvent extends Bet365FlashLiveEvent
{
    const DEFAULT_COUNT_HALFS = 4;
    const CACHE_KEY_HALF_DURATION = 'diff_Event_half_duration_';
    private $cacheEventHalfDuration;
    private $countHalfs;

    /*
     sport |      event |            ID |         SS |         XP |         PI |      TU |      TM |      TS |      UC |      CP
BASKETBALL | China vs P | 11912176A_1_3 |      36-28 |            |            |         |       3 |       9 |         |      Q2
BASKETBALL | Mongolia v | 11914037A_1_3 |      35-42 |            |            |         |       1 |      24 | Time Ou |      Q2

BASKETBALL | China vs P | 11912176A_1_3 |      38-32 |            |            |         |       1 |      53 |         |      Q2
BASKETBALL | Mongolia v | 11914037A_1_3 |      38-42 |            |            |         |      10 |       0 | At end  |      Q3

     sport |      event |            ID |    LM |    T2 |    DC |    SV |    VS |    TD |    VI |    MS |    C1 |    C2 |    CB
BASKETBALL | China vs P | 11912176A_1_3 |    22 |     5 |     1 |     1 |       |     1 |     0 |     0 |     1 | 13858 |      
BASKETBALL | Mongolia v | 11914037A_1_3 |    25 |     5 |     1 |     1 |       |     1 |     0 |     0 |     1 | 13855 |      
    */

    public function prepareEventAfterMerge()
    {
        $head = $this->item['raw_head'];

        if ($head['CP'] == 'OT')
        {
            $this->item['half'] = $this->getCountHalfs() + 1;
            $this->item['is_overtime'] = 1;
            if ($this->item['is_ended']) {
                $this->item['half']++;
            }
        }

        $halfDuration = $this->getEventHalfDuration((int) $head['TM']);
        $this->item['timer'] = $this->calcReverseTimer($head, $this->item, $halfDuration, 5);
        $this->item['timer_h'] = floor($this->item['timer'] / 60) . ':' . ($this->item['timer'] % 60);

        //echo sprintf("id:%s TT:%s TU:%s TM:%s TS:%s ch:%s hD:%s h:%s p:%s o:%s e:%s b:%s => t:%s %s\n", $this->id, $head['TT'], $head['TU'], $head['TM'], $head['TS'], $this->getCountHalfs(), $halfDuration, $this->item['half'], $this->item['is_pause'], $this->item['is_overtime'], $this->item['is_ended'], $this->item['is_breaknow'], $this->item['timer'], $this->item['timer_h']);
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

        $half = 1;
        $halfsOfQuarter = array(
            'Q1' => 1,
            'Q2' => 2,
            'Q3' => 3,
            'Q4' => 4,
        );
        $halfsOfTimes = array(
            '1st' => 1,
            '2nd' => 2,
        );
        if (isset($halfsOfQuarter[$data['CP']])) {
            $half = $halfsOfQuarter[$data['CP']];
        } elseif (isset($halfsOfTimes[$data['CP']])) {
            $half = $halfsOfTimes[$data['CP']];
            $this->setCountHalfs(2);
        }
        $res['half'] = $half;

        if ($data['UC'] == 'Time Out') {
            $res['is_breaknow'] = 1;
        }

        if (preg_match('/At.end.of.(\d)[stndrh]+.Quarter/', $data['UC'], $m))
        {
            $res['half'] = (int) $m[1] + 1;
            $res['is_pause'] = (int) $m[1];
            if ($res['half'] === 5 && $res['score_home'] != $res['score_away']) {
                $res['is_ended'] = 1;
            }
        }
        elseif (preg_match('/At.end.of.(\d)[stndrh]+.Half/', $data['UC'], $m))
        {
            $this->setCountHalfs(2);
            $res['half'] = (int) $m[1] + 1;
            $res['is_pause'] = (int) $m[1];
            if ($res['half'] === 2 && $res['score_home'] != $res['score_away']) {
                $res['is_ended'] = 1;
            }
        }
        elseif (preg_match('/At.end.of.Overtime/', $data['UC'], $m))
        {
            if ($res['score_home'] != $res['score_away']) {
                $res['is_ended'] = 1;
            }
        }

        return $res;
    }

    protected function processScore()
    {
        $res = parent::processScore();

        if (! isset($this->item['raw_score']['3']) && ! isset($this->item['raw_score']['4'])) {
            $this->setCountHalfs(2);
        }

        return $res;
    }

    protected function processStat()
    {
        $res = array();

        foreach ($this->item['raw_stat'] as $stat)
        {
            if (preg_match('/(\d+)-(\d+).score.at.the.end.of.(\d)[stndrh]+.Quarter/', $stat, $m))
            {
                $res['half'] = $m[3] + 1;
                if ($res['half'] === 5 && $m[1] != $m[2]) {
                    $res['is_ended'] = 1;
                }
            }
            elseif (preg_match('/(\d+)-(\d+).score.at.the.end.of.(\d)[stndrh]+.Half/', $stat, $m))
            {
                $this->setCountHalfs(2);
                $res['half'] = $m[3] + 1;
                if ($res['half'] === 3 && $m[1] != $m[2]) {
                    $res['is_ended'] = 1;
                }
            }
            elseif (preg_match('/(\d+)-(\d+).score.at.the.end.of.Overtime/', $stat, $m))
            {
                if ($m[1] != $m[2]) {
                    $res['is_ended'] = 1;
                }
            }
        }

        return $res;
    }

    private function getEventHalfDuration($TM)
    {
        if ($this->sport !== SportTypes::SPORT_BASKETBALL) {
            return SportTypes::$HALFS_DURATION_IN_SPORT[$this->sport];
        }

        if ($this->cacheEventHalfDuration) {
            $res = $this->cacheEventHalfDuration;
        } else {
            $res = MemoryCache::get(self::CACHE_KEY_HALF_DURATION . $this->id);
            if ($res !== false) {
                $res = unserialize($res);
            } else {
                $res = null;
            }
        }

        if ($res && $res >= $TM && in_array($res, array(10, 12, 20, 24))) {
            return $this->cacheEventHalfDuration = $res;
        }

        if ($TM > 20) {
            $res = 24;
        } elseif ($this->getCountHalfs() == 2 || $TM > 12) {
            $res = 20;
        } elseif ($TM > 10) {
            $res = 12;
        } elseif ($TM > 0) {
            $res = 10;
        } else {
            $res = null;
        }

        if ($res)
        {
            MemoryCache::replace(self::CACHE_KEY_HALF_DURATION . $this->id, serialize($res), false, 3600 * 6);
            $this->cacheEventHalfDuration = $res;
        }
        return $res;
    }

    private function getCountHalfs()
    {
        return $this->countHalfs ? $this->countHalfs : self::DEFAULT_COUNT_HALFS;
    }

    private function setCountHalfs($val)
    {
        $this->countHalfs = $val;
    }
}
