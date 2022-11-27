<?php declare(strict_types=1);

namespace Atsmacode\PokerGame\Classes\ActionHandler;

use Atsmacode\PokerGame\Classes\GameState\GameState;
use Atsmacode\PokerGame\Models\Hand;

interface ActionHandlerInterface
{
    /**
     * @param $int|null $betAmount
     */
    public function handle(
        Hand $hand,
        int  $playerId,
        int  $tableSeatId,
        int  $handStreetId,
             $betAmount,
        int  $actionId,
        int  $active,
        int  $stack
    ): GameState;
}