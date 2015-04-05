<?php

require_once 'level.php';

class Bet365LevelSportAA extends Bet365Level
{
    public function parseEventsFromSport()
    {
        $res = array();
        foreach ($this->getChildren() as $i => $sub) {
            $res = $this->parseEventsFromSportRec($sub, $res);
        }
        return $res;
    }

    private function parseEventsFromSportRec($dump, $res = array(), $sport = null)
    {
        if ($dump->getLevel() == Bet365Level::CL)
        {
            $sportId = (int) $dump->getVar('ID');
            if (isset(self::$mappedSports[$sportId]))
            {
                foreach ($dump->getChildren() as $i => $sub) {
                    $res = $this->parseEventsFromSportRec($sub, $res, self::$mappedSports[$sportId]);
                }
            }
        }
        elseif ($dump->getLevel() == Bet365Level::CT)
        {
            foreach ($dump->getChildren() as $i => $sub) {
                $res = $this->parseEventsFromSportRec($sub, $res, $sport);
            }
        }
        elseif ($dump->getLevel() == Bet365Level::EV && $sport)
        {
            // SD=0;  HP=1;  ET=0;  LO=1111;  TM=45;
            // EV;[^|]+;SD=\d;[^|]+;CT=([^;]+);[^|]+;ID=([^;]+);[^|]+;NA=([^;]+)
            if (is_numeric($dump->getVar('SD'))) {
                $res[] = $dump->setSport($sport);
            }
        }
        // else do not use other levels
        return $res;
    }
}
