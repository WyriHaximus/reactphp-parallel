<?php


use PackageVersions\Versions;
use React\EventLoop\Factory;
use function React\Promise\all;
use WyriHaximus\React\Parallel\Finite;
use function WyriHaximus\iteratorOrArrayToArray;
use WyriHaximus\React\Parallel\ReturnThread;
use WyriHaximus\React\Parallel\FiniteWorker;

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$loop = Factory::create();
echo 'Loop: ', get_class($loop), PHP_EOL;

$finite = new FiniteWorker($loop, 256, ReturnThread::class);

$calls = [];

foreach (range(1, 10000) as $i) {
    $calls[] = $finite->rpc('RPC_NAME', $i);
}

$fn = function ($versions) use ($finite, $loop) {
    var_export($versions);

    $finite->kill();
    $loop->stop();
};

all($calls)->then($fn, $fn)->done();

echo 'Loop::run()', PHP_EOL;
$loop->run();
echo 'Loop::done()', PHP_EOL;