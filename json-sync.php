<?php

$json = file_get_contents('events.json');

foreach (range(0, 100000) as $i) {
    json_decode($json);
}
