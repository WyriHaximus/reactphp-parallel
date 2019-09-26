<?php


use React\EventLoop\Factory;
use WyriHaximus\React\Parallel\Finite;
use function React\Promise\all;
use function WyriHaximus\iteratorOrArrayToArray;

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$loop = Factory::create();

$finite = Finite::create($loop, 100);

$timer = $loop->addPeriodicTimer(1, function () use ($finite) {
    var_export(iteratorOrArrayToArray($finite->info()));
});

$promises = [];
foreach (range(0, 250) as $i) {
    $promises[] = $finite->run(function($sleep) {
        sleep($sleep);
        return $sleep;
    }, [random_int(1, 13)])->then(function (int $sleep) use ($i) {
        echo $i, '; ', $sleep, PHP_EOL;

        return $sleep;
    });
}

$signalHandler = function () use ($finite, $loop) {
    $loop->stop();
    $finite->close();
};
all($promises)->then(function ($v) use ($finite, $loop, $signalHandler, $timer) {
    $finite->close();
    $loop->removeSignal(SIGINT, $signalHandler);
    $loop->cancelTimer($timer);
})->done();

$loop->addSignal(SIGINT, $signalHandler);

echo 'Loop::run()', PHP_EOL;
$loop->run();
echo 'Loop::done()', PHP_EOL;
