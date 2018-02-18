<?php

include 'autoloader.php';

$boardingCards = [
    [
        "type" => "Flight SU1480",
        "from" => "Moscow",
        "to"   => "Shanghai",
        "text" => "Gate 45, Seat 15C, you may take only 1 luggage piece",
    ],
    [
        "type" => "Airport bus",
        "from" => "Shanghai",
        "to"   => "Hangzhou",
        "text" => "Seat 5",
    ],
    [
        "type" => "taxi",
        "from" => "Hangzhou",
        "to"   => "HZ Citadiens",
        "text" => "",
    ],
    [
        "type" => "Work shatl",
        "from" => "HZ Citadiens",
        "to"   => "HZ LeJa campus",
        "text" => "",
    ],
    [
        "type" => "taxi",
        "from" => "HZ LeJa campus",
        "to"   => "HZ Citadiens",
        "text" => "",
    ],
    [
        "type" => "taxi",
        "from" => "HZ Citadiens",
        "to"   => "HZ Oakwood",
        "text" => "",
    ],
    [
        "type" => "walking",
        "from" => "HZ Oakwood",
        "to"   => "HZ Basement",
        "text" => "",
    ],
    [
        "type" => "taxi",
        "from" => "HZ Basement",
        "to"   => "HZ Eudora",
        "text" => "",
    ],
    [
        "type" => "taxi",
        "from" => "HZ Eudora",
        "to"   => "HZ Citadiens",
        "text" => "",
    ],
    [
        "type" => "walking",
        "from" => "HZ Citadiens",
        "to"   => "HZ Wallmart",
        "text" => "",
    ],
    [
        "type" => "walking",
        "from" => "HZ Wallmart",
        "to"   => "HZ Park",
        "text" => "",
    ],
    [
        "type" => "walking",
        "from" => "HZ Wallmart",
        "to"   => "HZ Park",
        "text" => "",
    ],
    [
        "type" => "undergroud",
        "from" => "HZ Park",
        "to"   => "HZ Citadiens",
        "text" => "",
    ],
    [
        "type" => "taxi",
        "from" => "HZ Citadiens",
        "to"   => "Hangzhou",
        "text" => "",
    ],
    [
        "type" => "Airport bus",
        "from" => "Hangzhou",
        "to"   => "Shanghai",
        "text" => "Seat 8",
    ],
    [
        "type" => "Flight SU4041",
        "from" => "Shanghai",
        "to"   => "Moscow",
        "text" => "Gate 22, Seat 72F, luggage 2PC",
    ],
];

$registry = new PointsRegistry($boardingCards);
$pathFinder = new PathFinder($registry);
try {
    echo "\n" . $pathFinder->searchPath("HZ Citadiens", "Moscow") . "\n";
} catch (PathFilderException $e) {
    echo $e;
}
