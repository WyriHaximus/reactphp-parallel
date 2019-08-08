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
$loop->addTimer(1, function () {
    echo 'ping', PHP_EOL;
});
echo 'Loop::run()', PHP_EOL;
$loop->run();
echo 'Loop::done()', PHP_EOL;