<?php
/**
 * Created by Vim.
 * User: yorick
 * Date: 01.12.14
 * Time: 11:10
 */

require_once 'Bet365FlashLive.php';

class Bet365FlashLiveFieldHockeyEvent extends Bet365FlashLiveEvent
{
    /*
     sport |      event |            ID |         SS |         XP |         PI |      TU |      TM |      TS |      UC |      CP

     sport |      event |            ID |    LM |    T2 |    DC |    SV |    VS |    TD |    VI |    MS |    C1 |    C2 |    CB
    */

    public function prepareEventAfterMerge()
    {
        $head = $this->item['raw_head2'];

        $half = 1;
        $halfsOfQuarter = array(
            '1st Quarter' => 1,
            '2nd Quarter' => 2,
            '3rd Quarter' => 3,
            '4th Quarter' => 4,
        );
        if (isset($halfsOfQuarter[$head['ED']])) {
            $half = $halfsOfQuarter[$head['ED']];
        }
        if (! isset($res['half']) || $this->item['half'] < $half) {
            $this->item['half'] = $half;
        }

        if ($head['ED'] == 'OT')
        {
            $this->item['half'] = $this->getCountHalfs() + 1;
            $this->item['is_overtime'] = 1;
            if ($this->item['is_ended']) {
                $this->item['half']++;
            }
        }

        $this->item['timer'] = $this->calcTimer($head);
        $this->item['timer_h'] = floor($this->item['timer'] / 60) . ':' . ($this->item['timer'] % 60);
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

        if (preg_match('/At.End.of.(\d)[stndrh]+.Quarter/', $data['UC'], $m))
        {
            $res['half'] = (int) $m[1] + 1;
            $res['is_pause'] = (int) $m[1];
            if ($res['half'] === 5 && $res['score_home'] != $res['score_away']) {
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
            elseif (preg_match('/(\d+)-(\d+).score.at.the.end.of.Overtime/', $stat, $m))
            {
                if ($m[1] != $m[2]) {
                    $res['is_ended'] = 1;
                }
            }
        }

        return $res;
    }
}
