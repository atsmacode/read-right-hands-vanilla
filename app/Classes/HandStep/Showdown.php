<?php

namespace App\Classes\HandStep;

use App\Classes\Dealer\Dealer;
use App\Classes\Game\Game;
use App\Classes\GameState\GameState;
use App\Classes\Showdown\Showdown as TheShowdown;
use App\Helpers\PotHelper;

/**
 * Responsible for the actions required if the hand has reached a showdown.
 */
class Showdown extends HandStep
{
    public function __construct(Game $game, Dealer $dealer)
    {
        $this->game   = $game;
        $this->dealer = $dealer;
    }

    public function handle(GameState $gameState): GameState
    {
        $this->gameState = $gameState;
        
        $this->gameState->setPlayers();

        $winner = (new TheShowdown($this->gameState))->compileHands()->decideWinner();

        $this->gameState->setWinner($winner);

        PotHelper::awardPot(
            $winner['player']['stack'],
            $this->gameState->getPot(),
            $winner['player']['player_id'],
            $winner['player']['table_id']
        );

        $this->gameState->getHand()->complete();

        return $this->gameState;
    }
}