<?php

//
//                       $o
//                       $                     .........
//                      $$$      .oo..     'oooo'oooo'ooooooooo....
//                       $       $$$$$$$
//                   .ooooooo.   $$!!!!!
//                 .'.........'. $$!!!!!      o$$oo.   ...oo,oooo,oooo'ooo''
//    $          .o'  oooooo   '.$$!!!!!      $$!!!!!       'oo''oooo''
// ..o$ooo...    $                '!!''!.     $$!!!!!
//$    ..  '''oo$$$$$$$$$$$$$.    '    'oo.  $$!!!!!
// !.......      '''..$$ $$ $$$   ..        '.$$!!''!
// !!$$$!!!!!!!!oooo......   '''  $$ $$ :o           'oo.
// !!$$$!!!$$!$$!!!!!!!!!!oo.....     ' ''  o$$o .      ''oo..
// !!!$$!!!!!!!!!!!!!!!!!!!!!!!!!!!!ooooo..      'o  oo..    $
//  '!!$$!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!oooooo..  ''   ,$
//   '!!$!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!oooo..$$
//    !!$!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!$'
//    '$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$!!!!!!!!!!!!!!!!!!,
//.....$$$$$$$$$$$$$$$$$$$$$$$$$http://www.asciiworld.com.....

use EventLoop\EventLoop;
use Rx\Scheduler\EventLoopScheduler;
use Rx\Websocket\Client;
use Rx\Websocket\MessageSubject;

require __DIR__ . "/vendor/autoload.php";

$state = [
    'ships' => [],
    'ships_ok' => false,
    'i_am_first_player' => false,
    'first_player_ok' => false,
    'adversary_shifumi_choice' => null,
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
    $ip = read("Please enter other player address [ip:port] : ");
    $check = explode(':', $ip);
    if (count($check) < 2) goto GET_IP;
    if (!filter_var($check[0], FILTER_VALIDATE_IP)) goto GET_IP;
    if (!filter_var($check[1], FILTER_VALIDATE_INT)) goto GET_IP;

    return $ip;
}

function startServer() {
    global $loop;

    GET_PORT:
    $port = read("Which port would you like to open to your friend : ");
    if (!filter_var($port, FILTER_VALIDATE_INT)) goto GET_PORT;

    $server = new \Rx\Websocket\Server('0.0.0.0:' . $port);

    $server->subscribe(function (\Rx\Websocket\MessageSubject $cs) {
        $cs->subscribe(function ($message) {
            global $state;

            //echo $message . PHP_EOL;

            $content = explode('|', $message);

            // NO ERROR MGT FOOL !

            switch ($content[0]) {
                case 'shifumi':
                    $state['adversary_shifumi_choice'] = $content[1];
                    break;
            }
        });
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

                    //defineShip($state['ships'], 5);
                    //defineShip($state['ships'], 4);
                    //defineShip($state['ships'], 3);
                    //defineShip($state['ships'], 3);
                    //defineShip($state['ships'], 2);

                    $state['ships_ok'] = true;
                }

                if (!$state['first_player_ok']) {
                    echo "We need a first player ! Let's go shifumi !" . PHP_EOL;

                    shifumi($ms);

                    $state['i_am_first_player'] = true;
                }

                echo "Ready to battle !" . PHP_EOL;


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

function defineShip(array &$state, $length)
{
    echo "Place your $length long ship" . PHP_EOL;

    GET_START_X:
    $sx = read("Git column for ship start position [A-H] : ");
    if (!strlen($sx)===1 || !in_array($sx, range('A', 'H'))) goto GET_START_X;

    GET_START_Y:
    $sy = read("Git raw for ship start position [0-7] : ");
    if (!strlen($sy)===1 || !in_array($sy, range(0, 7))) goto GET_START_Y;

    GET_END_X:
    $ex = read("Git column for ship start position [A-H] : ");
    if (!strlen($ex)===1 || !in_array($ex, range('A', 'H'))) goto GET_END_X;

    GET_END_Y:
    $ey = read("Git raw for ship start position [0-7] : ");
    if (!strlen($ey)===1 || !in_array($ey, range(0, 7))) goto GET_END_Y;

    // check axis
    if ($sx !== $ex && $sy !== $ey) {
        echo "Bad position" . PHP_EOL;
        goto GET_START_X;
    }

    // check length
    if ($sx === $ex) {
        if (abs($sy-$ey) !== $length-1) {
            echo "Bad length" . PHP_EOL;
            goto GET_START_X;
        }
    }
    if ($sy === $ey) {
        if (abs($sx-$ex) !== $length-1) {
            echo "Bad length" . PHP_EOL;
            goto GET_START_X;
        }
    }

    echo "Nice ship !" . PHP_EOL;

    // todo save in $state
}

function read($prompt)
{
    global $loop;

    $res = null;
    readline_callback_handler_install(
        $prompt,
        function ($mes) use (&$res) {
            global $loop;

            echo $mes . PHP_EOL;
            $res = $mes;

            readline_callback_handler_remove();
            $loop->removeReadStream(STDIN);
        }
    );
    $loop->addReadStream(STDIN, function() {
        readline_callback_read_char();
    });
    $x = \Rx\await(\Rx\Observable::of(null)->map(function () use (&$res) {
        if($res === null) throw new \Exception('rte');
    })->retry());

    foreach ($x as $rr) {}

    return $res;
}

function shifumi(MessageSubject $ms)
{
    global $state;

    GET_CHOICE:
    $choice = read("Make your choice [rock|paper|scissor] : ");
    if (!in_array($choice, ['rock', 'paper', 'scissor'])) goto GET_CHOICE;

    $ms->onNext('shifumi|' . $choice);

    $x = \Rx\await(\Rx\Observable::of(null)->map(function () use (&$state) {
        if($state['adversary_shifumi_choice'] === null) throw new \Exception('rte');
    })->retry());

    foreach ($x as $rr) {}

    echo "You picked `$choice` and you oponent picked `{$state['adversary_shifumi_choice']}`" . PHP_EOL;

    if ($choice === $state['adversary_shifumi_choice']) {
        echo "It's a draw game, play again ..." . PHP_EOL;
        $state['adversary_shifumi_choice'] = null;
        shifumi($ms);
        return;
    }

    if (
        $choice === 'rock' && $state['adversary_shifumi_choice'] === 'paper' ||
        $choice === 'paper' && $state['adversary_shifumi_choice'] === 'scissor' ||
        $choice === 'scissor' && $state['adversary_shifumi_choice'] === 'rock'
    ) {
        echo "You lose." . PHP_EOL;
        $state['i_am_first_player'] = false;
        $state['first_player_ok'] = true;
        return;
    }

    echo "You won !" . PHP_EOL;
    $state['i_am_first_player'] = true;
    $state['first_player_ok'] = true;
    return;

}

$loop->run();
