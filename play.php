<?php

use EventLoop\EventLoop;
use Rx\Scheduler\EventLoopScheduler;

require __DIR__ . "/vendor/autoload.php";

const RESULTS = [
    "touched" => 'TOUCHED',
    "sinked" => 'SINKED',
    "plouf" => 'PLOUF'
];

$loop = EventLoop::getLoop();
$scheduler = new EventLoopScheduler($loop);

$ip = getIp();




function getIp()
{
    GET_IP:
    $ip = readline("Please enter other player IP address : ");
    if (!filter_var($ip, FILTER_VALIDATE_IP)) goto GET_IP;

    return $ip;
}
