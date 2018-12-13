<?php

use QqBot\Bot;

require_once __DIR__ . '/vendor/autoload.php';

$bot = new Bot(include(__DIR__ . '/config.php'));

while (true) {
    ($message = $bot->getMessage()) && ((new \QqBot\Handle\Reply($message, $bot))->run());
}


