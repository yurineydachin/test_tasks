<?php
/**
 * Created by Vim.
 * User: yorick
 * Date: 26.09.14
 * Time: 08:50
 */

require_once 'Bet365FlashLive.php';

class Bet365FlashLiveTableTennisEvent extends Bet365FlashLiveEvent
{
    /*
     sport |      event |            ID |         SS |         XP |         PI |      TU |      TM |      TS |      UC |      CP
TABLE_TENN | Ondrej Baj | 11890908A_1_3 |        0-0 |            |        0,1 |         |       0 |       0 |         |        
TABLE_TENN | Jiri Vrabl | 11830419A_1_3 |        7-9 |  11-8,11-5 |        1,0 |         |       0 |       0 |         |        

VOLLEYBALL | South Kore | 12004303A_1_3 |        6-3 |            |        1,0 |         |       0 |       0 |         |        
VOLLEYBALL | Qatar v In | 12361302A_1_3 |      13-11 | 25-20,25-2 |        1,0 |         |       0 |       0 |         |        

 BADMINTON | Ha Tan Tha |  2273195A_1_3 |        6-8 |       6-21 |        1,0 |         |       0 |       0 |         |        
 BADMINTON | Ting Yu Li |  2284487A_1_3 |      10-10 |            |        1,0 |         |       0 |       0 |         |        
    */

    public function prepareEventAfterMerge()
    {
        if ($this->item['periods'])
        {
            $scoreHA = $this->calcScore($this->item);
            $this->item['score_home'] = $scoreHA[0];
            $this->item['score_away'] = $scoreHA[1];
        }

        return $this->item;
    }

    protected function processSport()
    {
        $res = array();
        $data = $this->item['raw_head'];
        $res['service'] = $data['PI'] == '1,0' ? 1 : 2;

        $res['periods'] = $this->calcScorePeriods($data['XP'] . ($data['XP'] ? ',' : '') . $data['SS']);

        if ($res['periods']) {
            $res['half'] = count($res['periods']);
        }
        if ($data['UC'] == 'Time Out' || $data['UC'] == 'Technical Time Out') {
            $res['is_breaknow'] = 1;
        }
        if (preg_match('/At.end.of.Set.(\d).*(\d+)-(\d+)/', $data['UC'], $m) ||
            preg_match('/Game.(\d).*(\d+)-(\d+)/', $data['UC'], $m))
        {
            $res['is_pause'] = (int) $m[1];
            $res['half']     = (int) $m[1] + 1;
        }

        return $res;
    }

    protected function calcScore($item)
    {
        $periods = $item['periods'];
        if (! isset($item['is_ended'])) {
            $scoreLastPeriod = array_pop($periods);
        }
        $score = array(0, 0);
        foreach ($periods as $scoreHA)
        {
            if ($scoreHA['home'] > $scoreHA['away']) {
                $score[0]++;
            } else {
                $score[1]++;
            }
        }
        return $score;
    }

    protected function processStat()
    {
        $res = array();

        foreach ($this->item['raw_stat'] as $stat)
        {
            if (preg_match('/(\d+)-(\d+).score.at.the.end.of.(Game|Set).(\d)/', $stat, $match))
            {
                $res['half'] = $match[4] + 1;
                $res['periods'][$match[4] - 1] = array(
                    'home' => (int) $match[1],
                    'away' => (int) $match[2],
                );
            }
        }

        return $res;
    }
}
