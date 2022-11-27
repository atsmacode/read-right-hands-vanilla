<?php

use Atsmacode\PokerGame\Classes\ActionHandler\ActionHandler;
use Atsmacode\PokerGame\Classes\GameData\GameData;
use Atsmacode\PokerGame\Classes\GameState\GameState;
use Atsmacode\PokerGame\Controllers\HandController;
use Atsmacode\PokerGame\Controllers\PlayerActionController;

if (!isset($GLOBALS['dev'])) {
    $GLOBALS['THE_ROOT'] = '../';
}

require_once($GLOBALS['THE_ROOT'] . 'vendor/autoload.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (str_contains($_SERVER['REQUEST_URI'], 'play')) {
        return (new HandController())->play();
    }

    if (str_contains($_SERVER['REQUEST_URI'], 'action')) {
        return (new PlayerActionController(
            new ActionHandler(new GameState(new GameData()))
        ))->action();
    }
}

if (str_contains($_SERVER['REQUEST_URI'], 'play')) {
    require($GLOBALS['THE_ROOT'] . 'resources/play.php');
} else {
    require($GLOBALS['THE_ROOT'] . 'resources/index.php');
}
