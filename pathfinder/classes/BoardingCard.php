<?php

class BoardingCard {
    private $to;
    private $type;
    private $text;

    public function __construct(Point $to, $type = "", $text = "") {
        $this->to = $to;
        $this->type = $type;
        $this->text = $text;
    }

    public function getTo() {
        return $this->to;
    }

    public function getToName() {
        return $this->to->getName();
    }

    public function getType() {
        return $this->type;
    }

    public function getText() {
        return $this->text;
    }
}
