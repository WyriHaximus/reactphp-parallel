<?php


use React\EventLoop\Factory;
use function React\Promise\all;
use WyriHaximus\React\Parallel\Finite;

$json = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'large.json');

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$loop = Factory::create();

$finite = new Finite($loop, 150);

$promises = [];
foreach (range(0, 5000) as $i) {
    $promises[] = $finite->run(function($json) {
        $json = json_decode($json, true);
        return md5(json_encode($json));
    }, [$json]);
}

$signalHandler = function () use ($finite, $loop) {
    $loop->stop();
    $finite->close();
};
all($promises)->then(function ($v) {
    var_export($v);
})->always(function () use ($finite, $loop, $signalHandler) {
    $finite->close();
    $loop->removeSignal(SIGINT, $signalHandler);
})->done();

$loop->addSignal(SIGINT, $signalHandler);

echo 'Loop::run()', PHP_EOL;
$loop->run();
echo 'Loop::done()', PHP_EOL;
