# Ship Battle
Best ship battle ever

## Abstract

A ship battle game based on a  wide sharded ocean. Ships can move.

## Board

The board is a 16x16 square. Columns are called from A to P. Raws are called from 0 to 15.

## Set Up

Player launch the game console : `php play.php`

Player is asked for the other player's IP. Players are connected with ZMQ. If disconnected, the game is paused and wait for reconexion. A unique token must certify the game state.

Each player must define its ships positons.

Ships sizes :

* 1x5
* 1x4
* 2x3
* 1x2

For each ship, player defines the head and queue postions. The ship check for the length respect, the board respect and the collisons. It keeps asking the same ship while not correct.

A first player is randomly polled.

He must enter coord for a shoot.

The game returns with `TOUCHED`, `SINKED`, `PLOUF`.

And diplays the current board state.

`O` for an unshot place.

`x` for a touch

`w` for a missed shout

The game ends when a player has no boats anymore.

The game prompts the winner and exits.

## Afterward

[] increase board size

[] share ocean

[] improve player number
