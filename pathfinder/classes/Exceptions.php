<?php

class PathFilderException extends Exception {}

class UnknownPointExeption extends PathFilderException {
    public function __construct (Point $from = null, Point $to = null , $code = 0 , Throwable $previous = NULL) {
        parent::__construct("Unknow point to search path, from: $from, to: $to", $code);
    }
}

class EqualPointExeption extends PathFilderException {
    public function __construct ($message = "" , $code = 0 , Throwable $previous = NULL) {
        parent::__construct("Start and end points are already equal: " . $message, $code);
    }
}

class RouteNotFoundExeption extends PathFilderException {
    public function __construct ($message = "" , $code = 0 , Throwable $previous = NULL) {
        parent::__construct("Route not found: " . $message, $code);
    }
}
