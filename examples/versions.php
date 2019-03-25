<?php


use PackageVersions\Versions;
use React\EventLoop\Factory;
use WyriHaximus\React\Parallel\Finite;

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$loop = Factory::create();

$finite = new Finite($loop, 1);

$finite->run(function () {
    return Versions::VERSIONS;
})->done(function ($versions) use ($finite) {
    var_export($versions);

    $finite->close();
});

echo 'Loop::run()', PHP_EOL;
$loop->run();
echo 'Loop::done()', PHP_EOL;