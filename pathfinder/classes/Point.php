<?php

class Point {
    private $name;
    private $cards = [];

    public function __construct($name) {
        $this->name = $name;
    }

    public function getName() {
        return $this->name;
    }

    public function addCard(BoardingCard $card) {
        $this->cards[$card->getToName()] = $card;
    }

    public function getCards() {
        return $this->cards;
    }

    public function equals(Point $to) {
        return $this->getName() == $to->getName();
    }

    public function __toString() {
        return $this->name;
    }
}
