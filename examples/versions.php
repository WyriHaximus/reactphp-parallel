<?php


use PackageVersions\Versions;
use React\EventLoop\Factory;
use WyriHaximus\React\Parallel\Finite;
use function WyriHaximus\iteratorOrArrayToArray;

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$loop = Factory::create();

$finite = new Finite($loop, 2);

$timer = $loop->addPeriodicTimer(1, function () use ($finite) {
    var_export(iteratorOrArrayToArray($finite->info()));
});
$finite->run(function () {
    return Versions::VERSIONS;
})->then(function ($versions) use ($finite, $loop, $timer) {
    var_export($versions);

    $finite->close();
    $loop->cancelTimer($timer);
})->done();

echo 'Loop::run()', PHP_EOL;
$loop->run();
echo 'Loop::done()', PHP_EOL;