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

$parallel = new \parallel\Runtime();
$channel = Channel::make("channel", Channel::Infinite);
$input = new Input();
$input->add("channel", "input");
$events = new Events();
$events->setTimeout(1);
$events->setInput($input);
$events->addChannel($channel);
$events->addFuture("future", $parallel->run(function(){
    sleep(1);
    return 42;
}));

$loop = Factory::create();

$loop->addPeriodicTimer(0.001, function () use ($events, $channel, $loop, &$timer) {
    try {
        $event = $events->poll();
    } catch (Timeout $timeout) {
        echo microtime(true), 'timeout', PHP_EOL;
        return;
    }

    echo microtime(true), 'tick', PHP_EOL;

    if (!($event instanceof Event)) {
        return;
    }

    switch ($event->type) {
        case Event::Read:
            echo microtime(true), 'read', PHP_EOL;
            if ($event->object instanceof Future &&
                $event->value == 42) {
                echo microtime(true), "OK:Future:" . $event->value . "\n";
            }

            if ($event->object instanceof Channel &&
                $event->value == "input") {
                echo microtime(true), "OK:Channel\n";
            }
            break;

        case Event::Write:
            echo microtime(true), 'write', PHP_EOL;
            $events->addChannel($channel);
            break;
    }
});

$loop->run();
