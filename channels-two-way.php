<?php

require 'vendor/autoload.php';

use \parallel\Runtime;
use \parallel\Channel;
use \parallel\Future;
use \parallel\Events;
use \parallel\Events\Event;
use \parallel\Events\Input;
use \parallel\Events\Timeout;
use React\EventLoop\Factory;

$parallels = [];
$parallels[] = new \parallel\Runtime();
$parallels[] = new \parallel\Runtime();
$parallels[] = new \parallel\Runtime();
$parallels[] = new \parallel\Runtime();
$parallels[] = new \parallel\Runtime();
$parallels[] = new \parallel\Runtime();
$parallels[] = new \parallel\Runtime();
$parallels[] = new \parallel\Runtime();
$parallels[] = new \parallel\Runtime();
$parallels[] = new \parallel\Runtime();
$parallels[] = new \parallel\Runtime();
$parallels[] = new \parallel\Runtime();
$parallels[] = new \parallel\Runtime();
$parallels[] = new \parallel\Runtime();
$parallels[] = new \parallel\Runtime();
$parallels[] = new \parallel\Runtime();
$channel = Channel::make("channel", Channel::Infinite);
$channelTwo = Channel::make("channelTwo", Channel::Infinite);
$events = new Events();
$events->setTimeout(0);
$events->addChannel($channel);

$f = function($channel, $channelTwo) {
    $ri = random_int(1, 1000);
    $channel = Channel::open($channel);
    $channelTwo = Channel::open($channelTwo);

    while ($int = $channelTwo->recv()) {
        sleep(1);
        echo $ri . ':' . $int . PHP_EOL;
        $channel->send(($int * 13));
    }

    echo 'true', PHP_EOL;
    return true;
};

$futures = [];
foreach ($parallels as $parallel) {
    $futures[] = $parallel->run($f, [(string)$channel, (string)$channelTwo]);
}

$loop = Factory::create();


$loop->futureTick(function () use ($channelTwo) {
    foreach (range(1, 500) as $i) {
        $channelTwo->send($i);
    }
});

// Call 1K times per second
$loop->addPeriodicTimer(0.001, function () use ($events, $channel, $loop, &$timer) {
    echo 'tick';
    try {
        while ($event = $events->poll()) {
            if (!($event instanceof Event)) {
                return;
            }

            switch ($event->type) {
                case Event::Read:
                    echo microtime(true) .  ':read:' . var_export($event->value, true) . "\n";
                    break;
            }

            /* $event->object removed
                from Events when Event emitted */
            $events->addChannel($event->object);
        }
    } catch (Timeout $timeout) {
        return;
    } catch (Throwable $throwable) {
        echo (string)$throwable;
    }

});

// Terminate on SIGINT
$loop->addSignal(SIGINT, function () use ($parallels, $loop, $channel, $channelTwo) {
    foreach ($parallels as $parallel) {
        $parallel->close();
    }
    $loop->stop();

    // There might be some cleanup issues here with the channels
    $channel->close();
    $channelTwo->close();
});

$loop->run();
