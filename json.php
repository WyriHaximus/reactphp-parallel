<?php


$json = file_get_contents('events.json');

$runtimes = [];
$futures = [];

foreach (range(0, 150) as $i) {
    $runtimes[$i] = new \parallel\Runtime();
}


$i = 0;
foreach (range(0, 100000) as $j) {
    if (!isset($runtimes[$i])) {
        $i = 0;
    }
    $futures[$j] = $runtimes[$i]->run(function($json) {
        return json_decode($json);
    }, [$json]);
    $i++;
}


do {
    echo ".";
} while (count(array_filter($futures, function ($future) {
    return $future->done() === false;
})) > 0);

echo count($futures), PHP_EOL;
