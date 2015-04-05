<?php

require_once 'level.php';

class Bet365LevelDetailEV extends Bet365Level
{
    public function getSport()
    {
        if (! isset($this->data['sport']))
        {
            $sportId = (int) $this->getVar('CL');
            if (isset(self::$mappedSports[$sportId])) {
                $this->data['sport'] = self::$mappedSports[$sportId];
            } else {
                $this->data['sport'] = null;
            }
        }
        return $this->data['sport'];
    }

    protected function removeRoot()
    {
        $this->getListener()->removeEvent($this);
        return $this;
    }
}
