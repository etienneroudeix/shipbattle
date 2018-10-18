<?php

use EventLoop\EventLoop;
use Rx\Scheduler\EventLoopScheduler;
use Rx\Websocket\Client;

require __DIR__ . "/vendor/autoload.php";

$state = [
    'ships' => [],
    'grid' => [
        'A' => [0,0,0,0,0,0,0,0],
        'B' => [0,0,0,0,0,0,0,0],
        'C' => [0,0,0,0,0,0,0,0],
        'D' => [0,0,0,0,0,0,0,0],
        'E' => [0,0,0,0,0,0,0,0],
        'F' => [0,0,0,0,0,0,0,0],
        'G' => [0,0,0,0,0,0,0,0],
        'H' => [0,0,0,0,0,0,0,0],
    ],
    'ships_ok' => false,
];

$loop = EventLoop::getLoop();
$scheduler = new EventLoopScheduler($loop);

startServer();

$ip = getIp();

echo "Waiting for other player ..." . PHP_EOL;

$client = new Client('ws://' . $ip);
connect($client);

/* FUNCTIONS */
function getIp()
{
    GET_IP:
    $ip = readline("Please enter other player address [ip:port] : ");
    $check = explode(':', $ip);
    if (count($check) < 2) goto GET_IP;
    if (!filter_var($check[0], FILTER_VALIDATE_IP)) goto GET_IP;
    if (!filter_var($check[1], FILTER_VALIDATE_INT)) goto GET_IP;

    return $ip;
}

function startServer() {
    GET_PORT:
    $port = readline("Which port would you like to open to your friend : ");
    if (!filter_var($port, FILTER_VALIDATE_INT)) goto GET_PORT;

    $server = new \Rx\Websocket\Server('127.0.0.1:' . $port);

    $server->subscribe(function (\Rx\Websocket\MessageSubject $cs) {
        $cs->subscribe($cs);
    });
}

function connect(Client $client)
{
    $client
        ->retry()
        ->subscribe(
            function (\Rx\Websocket\MessageSubject $ms) {
                global $state;

                echo "Your friend is here !" . PHP_EOL;

                $ms->subscribe(
                    function ($message) {
                        echo $message . "\n";
                    }
                );

                if (!$state['ships_ok']) {
                    echo "Please define your ships !" . PHP_EOL;

                    defineShip(5, 'A');
                    defineShip(4, 'B', 'y');
                    defineShip(3, 'E');
                    defineShip(3, 'H', 'y');
                    defineShip(2, 'C');

                    $state['ships_ok'] = true;
                }

                //$ms->onNext('Hello');
            },
            function ($error) {
                echo "Could not connect" . PHP_EOL;
            },
            function () use ($client) {
                echo "Your friend is gone. Trying to reconnect..." . PHP_EOL;
                connect($client);

            }
        );
}

/**
 * @param int $length
 * @param string $start a0...h7
 * @param string $axis
 * @return bool
 */
function defineShip(int $length, string $start, string $axis = 'x')
{
    global $state;
    $startRow = $start[0];
    $axis = $axis === 'x' ? true : false;

    if($axis) {
        $len = $state['grid'][$startRow][$start[1] + $length];

        foreach ($len as $item) {
            if ($item != 0) {
                return false;
            }
        }

        $i = $start;
        DEFINE_SHIP_X_START_LOOP:
        $state['grid'][$len][$i] = 1;
        $i++;
        if ($i <= $start + $length) goto DEFINE_SHIP_X_START_LOOP;
    } else {
        // @TODO Implement Y axis
    }

}