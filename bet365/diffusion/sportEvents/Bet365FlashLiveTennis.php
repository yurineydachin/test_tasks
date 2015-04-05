<?php
/**
 * Created by Vim.
 * User: yorick
 * Date: 26.09.14
 * Time: 08:50
 */

require_once 'Bet365FlashLive.php';

class Bet365FlashLiveTennisEvent extends Bet365FlashLiveEvent
{
    /*
     sport |      event |            ID |         SS |         XP |         PI |      TU |      TM |      TS |      UC |      CP
    TENNIS | Marcel Gra | 11801916A_1_3 |        3-4 |        0-0 |        1,0 |         |       0 |       0 |         |        
    TENNIS | Mikhail Yo | 11812821A_1_3 |        3-3 |       0-40 |        1,0 |         |       0 |       0 |         |        
    TENNIS | Pierre-Hug | 11909470A_1_3 |        0-0 |        0-0 |        0,0 |         |       0 |       0 |         |        
    TENNIS | Serena Wil | 11808875A_1_3 |        0-5 |       0-15 |        0,1 |         |       0 |       0 |         |        
    TENNIS | Julia Helb | 11908088A_1_3 |        0-0 |        0-0 |        1,0 |         |       0 |       0 |         |        
    TENNIS | Alisa Mart | 11890002A_1_3 |        0-0 |        0-0 |        1,0 |         |       0 |       0 |         |        
    TENNIS | Halep/Olar | 11827787A_1_3 |        3-4 |        0-0 |        1,0 |         |       0 |       0 |         |        
    TENNIS | K. Polking | 11820071A_1_3 |        3-3 |      40-30 |        1,0 |         |       0 |       0 |         |        
    TENNIS | Nestor/Zim | 11793422A_1_3 |    6-3,1-2 |      30-15 |        1,0 |         |       0 |       0 |         |        
    TENNIS | Berdych/Is | 11806267A_1_3 |    7-5,0-0 |        0-0 |        1,0 |         |       0 |       0 |         |        
    TENNIS | Addison/Gu | 11807968A_1_3 |    4-6,1-0 |        0-0 |        0,1 |         |       0 |       0 |         |        
    TENNIS | Ito/Onozaw | 11768036A_1_3 |    7-6,1-0 |      40-15 |        1,0 |         |       0 |       0 |         |        
    */

    protected function processSport()
    {
        $res = array();
        $data = $this->item['raw_head'];
        $res['service'] = $data['PI'] == '1,0' ? 1 : 2;

        $res['periods'] = $this->calcScorePeriods($data['SS']);

        if ($res['periods'])
        {
            $scoreHA = $this->calcScore($res['periods']);
            $res['score_home'] = $scoreHA[0];
            $res['score_away'] = $scoreHA[1];
            $res['half'] = count($res['periods']);
        }

        if ($data['XP'])
        {
            if ($scoreHA = $this->parseOneScore($data['XP']))
            {
                if (($scoreHA[0] === 'A' || $scoreHA[0] === '0' || $scoreHA[0] > 10) && ($scoreHA[1] === 'A' || $scoreHA[1] === '0' || $scoreHA[1] > 10))
                {
                    $res['game_home'] = $scoreHA[0] === 'A' ? 50 : (int) $scoreHA[0];
                    $res['game_away'] = $scoreHA[1] === 'A' ? 50 : (int) $scoreHA[1];
                }
                else
                {
                    $res['is_tiebreak']   = 1;
                    $res['tiebreak_home'] = (int) $scoreHA[0];
                    $res['tiebreak_away'] = (int) $scoreHA[1];
                }
            } else {
                echo "Event {$this->id}, Error: game_score '{$data['XP']}' does not have d-d\n";
            }
        }

        return $res;
    }

    protected function calcScore($periods)
    {
        $scoreLastPeriod = array_pop($periods);
        $score = array(0, 0);
        foreach ($periods as $scoreHA)
        {
            if ($scoreHA['home'] > $scoreHA['away']) {
                $score[0]++;
            } else {
                $score[1]++;
            }
        }
        $score[0] += $scoreLastPeriod['home'] == 7 ? 1 : 0;
        $score[1] += $scoreLastPeriod['away'] == 7 ? 1 : 0;
        return $score;
    }
}
