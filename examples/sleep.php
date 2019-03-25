<?php


use React\EventLoop\Factory;
use function React\Promise\all;
use function React\Promise\resolve;
use WyriHaximus\React\Parallel\Finite;

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$loop = Factory::create();

$finite = new Finite($loop, 250);

$promises = [];
foreach (range(0, 1000) as $i) {
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
all($promises)->done(function ($v) use ($finite, $loop, $signalHandler) {
    $finite->close();
    $loop->removeSignal(SIGINT, $signalHandler);
});

$loop->addSignal(SIGINT, $signalHandler);

echo 'Loop::run()', PHP_EOL;
$loop->run();
echo 'Loop::done()', PHP_EOL;