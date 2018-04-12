<?php

!defined("USE_CACHE") && define("USE_CACHE", true);

if (count($argv) < 5) {
    exit("Not enough params. Need <url> <start_time> <end_time> <places>\n");
}

if (!preg_match("/\d{2}-\d{2}-\d{2}\\T\d{2}:\d{2}/", $argv[2])) {
    exit(sprintf("Wrong format start_time: %s. Need format Y-m-d\\\\TH:i\n", $argv[2]));
}
if (!preg_match("/\d{2}-\d{2}-\d{2}\\T\d{2}:\d{2}/", $argv[3])) {
    exit(sprintf("Wrong format end_time: %s. Need format Y-m-d\\\\TH:i\n", $argv[3]));
}
$start = strtotime($argv[2]);
if ($start <= 0) {
    exit(sprintf("Wrong format start_time: %s. Need format Y-m-d\\\\TH:i\n", $argv[2]));
}
$end = strtotime($argv[3]);
if ($end <= 0) {
    exit(sprintf("Wrong format end_time: %s. Need format Y-m-d\\\\TH:i\n", $argv[3]));
}
$places = intval($argv[4]);
if ($places < 1 || $places > 30) {
    exit(sprintf("Wrong places: %s. Number %d of travelers - an integer between 1 and 30.\n", $argv[4], $places));
}

$json = json_decode(getData($argv[1], USE_CACHE), true);
if (!isset($json["product_availabilities"]) || !is_array($json["product_availabilities"])) {
    exit("Wrong endpoint data. Need {\"product_availabilities\": [{product},...]}\n");
}

echo json_encode(filterProductList($json["product_availabilities"], $start, $end, $places), JSON_PRETTY_PRINT) . "\n";


function filterProductList($list, $start, $end, $places) {
    $res = [];
    foreach($list as $row) {
        if (!isset($row["places_available"]) || !isset($row["product_id"]) || !isset($row["activity_start_datetime"]) || !isset($row["activity_duration_in_minutes"])) {
            echo "slip row: " . json_encode($row) . "\n";
            continue;
        }
        if ($row["places_available"] < $places) {
            continue;
        }
        $s = strtotime($row["activity_start_datetime"]);
        if ($s < $start) {
            continue;
        }
        if ($s + $row["activity_duration_in_minutes"] * 60 > $end) {
            continue;
        }
        if (!isset($res[$row["product_id"]])) {
            $res[$row["product_id"]] = [
                "product_id" => $row["product_id"],
                "available_starttimes" => [
                    $row["activity_start_datetime"],
                ],
            ];
        } else {
            $res[$row["product_id"]]["available_starttimes"][] = $row["activity_start_datetime"];
        }
    }
    if (count($res) > 0) {
        ksort($res);
        return array_values($res);
    }
    return [];
}

function getData($url, $useCache = false) {
    $name = md5($url);
    if ($useCache && file_exists($name)) {
        return file_get_contents($name);
    }
    $data = file_get_contents($url);
    $useCache && file_put_contents($name, $data);
    return $data;
}
