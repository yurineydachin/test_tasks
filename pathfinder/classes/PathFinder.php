<?php

include 'classes/Exceptions.php';

class PathFinder {
    private $registry;

    public function __construct(PointsRegistry $registry) {
        $this->registry = $registry;
    }

    public function searchPath($fromName, $toName) {
        $from = $this->registry->getByName($fromName);
        $to = $this->registry->getByName($toName);
        if (!$from || !$to) {
            throw new UnknownPointExeption($from, $to);
        }
        if ($from->equals($to)) {
            throw new EqualPointExeption();
        }

        $knownPoints = [$from->getName() => true];

        $routes = [];
        foreach ($from->getCards() as $card) {
            $route = new Route($card);
            if ($to->equals($card->getTo())) {
                return $route;
            }
            $routes[] = $route;
            $knownPoints[$card->getToName()] = true;
        }

        while (count($routes) > 0) {
            $newRoutes = [];
            foreach ($routes as $route) {
                foreach ($route->getCard()->getTo()->getCards() as $card) {
                    if (!isset($knownPoints[$card->getToName()])) {
                        $newRoute = new Route($card, $route);
                        if ($to->equals($card->getTo())) {
                            return $newRoute;
                        }
                        $newRoutes[] = $newRoute;
                        $knownPoints[$card->getToName()] = true;
                    } // else skip route
                }
            }
            $routes = $newRoutes;
        }
        throw new RouteNotFoundExeption();
    }
}
