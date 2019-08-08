<?php
use \parallel\Runtime;
use \parallel\Channel;
use \parallel\Events;

/* 4 runtimes ... */
$runtimes = [
    new Runtime,
    new Runtime,
    new Runtime,
    new Runtime,
];

/* Sending to two channels */
$channels = [
    Channel::make("one", Channel::Infinite),
    Channel::make("two", Channel::Infinite)
];

foreach ($runtimes as $idx => $runtime) {
    $runtime->run(function($idx, $channels){
        $channels = [
            Channel::open($channels[0]),
            Channel::open($channels[1])
        ];

        while (1) {
            /* random channel, random junk */
            $channels[
            array_rand($channels)
            ]->send([
                "from" => $idx,
                "rand" => mt_rand()
            ]);
        }
    }, [$idx, ["one", "two"]]);
}

$events = new Events;
$events->addChannel($channels[0]);
$events->addChannel($channels[1]);

/* process all events */
foreach ($events as $event) {
    var_dump($event);

    /* $event->object removed
        from Events when Event emitted */
    $events->addChannel($event->object);
}
