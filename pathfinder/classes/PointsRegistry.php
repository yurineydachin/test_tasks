<?php

class PointsRegistry {
    public $points = [];

    public function __construct($boardingCards) {
        foreach ($boardingCards as $card) {
            $this->createByName($card["from"])->addCard(
                new BoardingCard($this->createByName($card["to"]), $card["type"], $card["text"])
            );
        }
    }

    private function createByName($name) {
        return isset($this->points[$name]) ? $this->points[$name] : $this->points[$name] = new Point($name);
    }

    public function getByName($name) {
        return isset($this->points[$name]) ? $this->points[$name] : null;
    }
}
