<?php

class Route {
    private $card;
    private $parent;

    public function __construct(BoardingCard $card, Route $parent = null) {
        $this->card = $card;
        $this->parent = $parent;
    }

    public function getCard() {
        return $this->card;
    }

    public function __toString() {
        return ($this->parent ? $this->parent : "") . " -> " . $this->card->getToName();
    }

    public function getPrintInfo() {
        $result = [];
        if ($this->parent) {
            $result = array_merge($result, $this->parent->getPrintInfo());
        }
        $result[] = sprintf("Take %s to %s. %s", $this->card->getType(), $this->card->getToName(), $this->card->getText());
        return $result;
    }

    public function print() {
        $i = 1;
        foreach ($this->getPrintInfo() as $str) {
            echo sprintf("%d. %s\n", $i++, $str);
        }
    }
}
