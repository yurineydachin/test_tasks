<?php
/**
 * Created by Vim.
 * User: yorick
 * Date: 26.09.14
 * Time: 08:50
 */

require_once 'Bet365FlashLive.php';

class Bet365FlashLiveBaseballEvent extends Bet365FlashLiveEvent
{
    /*
     sport |      event |            ID |         SS |         XP |         PI |      TU |      TM |      TS |      UC |      CP
  BASEBALL | Yakult Swa | 12124659A_1_3 |       7-12 |            |            |         |       0 |       0 | Bases E |        
  BASEBALL | Seibu Lion | 12128940A_1_3 |        1-4 |            |            |         |       0 |       0 | Bases E |        
  BASEBALL | Yokohama B | 12144643A_1_3 |        0-0 |            |            |         |       0 |       0 | Bases E |        

     sport |      event |            ID |    LM |    T2 |    DC |    SV |    VS |    TD |    VI |    MS |    C1 |    C2 |    CB
  BASEBALL | Yakult Swa | 12124659A_1_3 |    13 |     5 |     0 |     1 |       |     0 |     0 |     0 |     1 |   238 |      
  BASEBALL | Seibu Lion | 12128940A_1_3 |     2 |     5 |     0 |     1 |       |     0 |     0 |     0 |     1 |   241 |      
  BASEBALL | Yokohama B | 12144643A_1_3 |     1 |     5 |     0 |     1 |       |     0 |     0 |     0 |     1 |   237 |      
    */

    protected function prepareEventAfterMerge()
    {
        $head  = $this->item['raw_head'];
        $head2 = $this->item['raw_head2'];

        $halfsOfInning = array(
            'Inning 1' => 1,
            'Inning 2' => 2,
            'Inning 3' => 3,
            'Inning 4' => 4,
            'Inning 5' => 5,
            'Inning 6' => 6,
            'Inning 7' => 7,
            'Inning 8' => 8,
            'Inning 9' => 9,
        );
        if (isset($halfsOfInning[$head2['ED']]))
        {
            $half = $halfsOfInning[$head2['ED']];
            $this->item['half'] = $half;
        }

        if ($data['UC'] == 'End Inning') {
            $this->item['pause'] = $this->item['half'] - 1;
        }
    }

    protected function processStat()
    {
        $res = array();

        foreach ($this->item['raw_stat'] as $stat)
        {
            if (preg_match('/End.of.*.(\d)[stndrh]+,.\d+.hits,.\d+.run,.*lead.(\d+)-(\d+)/', $stat, $match))
            {
                $res['periods'][$match[1] - 1] = array(
                    'home' => $match[2],
                    'away' => $match[3],
                );
                $res['half'] = $match[1] + 1;
            }
        }

        return $res;
    }
}
