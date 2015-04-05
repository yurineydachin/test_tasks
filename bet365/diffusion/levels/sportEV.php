<?php

require_once 'level.php';

class Bet365LevelSportEV extends Bet365Level
{
    public function getSport()
    {
        if (! isset($this->data['sport']))
        {
            $this->data['sport'] = null;
            $obj = $this->getParent();;
            while ($obj)
            {
                $id = (int) $obj->getVar('ID');
                if ($obj->getLevel() == self::CL && isset(self::$mappedSports[$id])) {
                    $this->data['sport'] = self::$mappedSports[$id];
                    break;
                }
                $obj = $obj->getParent();
            }
        }
        return $this->data['sport'];
    }

    protected function processInsert($message)
    {
        if (count($message['topics']) == 1) {
            parent::processInsert($message);
        } // else do not change children. We are not using it.
    }

    protected function processUpdate($message)
    {
        if (count($message['topics']) == 1) {
            parent::processUpdate($message);
        } // else do not change children. We are not using it.
    }

    protected function processDelete($message)
    {
        if (count($message['topics']) == 1) {
            parent::processDelete($message);
        } // else do not change children. We are not using it.
    }

    protected function removeSelf()
    {
        $this->getListener()->removeEventSport($this);
        return $this;
    }
}
