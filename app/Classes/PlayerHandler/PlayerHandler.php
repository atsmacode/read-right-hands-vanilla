<?php declare(strict_types=1);

namespace App\Classes\PlayerHandler;

use App\Classes\GameState\GameState;
use App\Constants\Action;
use App\Models\TableSeat;

/**
 * Responsible for returning the status and options for the players in a hand.
 */
class PlayerHandler implements PlayerHandlerInterface
{
    public function handle(GameState $gameState): array
    {
        $this->gameState = $gameState; $playerData  = []; $actionOnGet = $this->getActionOn();

        $this->gameState->setWholeCards();

        foreach($this->gameState->getPlayers() as $playerAction){
            $actionOn   = $actionOnGet && $actionOnGet['player_id'] === $playerAction['player_id'] ? true : false;
            $actionName = $playerAction['action_id'] ? $playerAction['actionName'] : null;
            $stack      = $playerAction['stack'];
            $wholeCards = isset($this->gameState->getWholeCards()[$playerAction['player_id']]) 
                ? $this->gameState->getWholeCards()[$playerAction['player_id']]
                : [];

            $playerData[] = [
                'stack'            => $stack ?? null,
                'name'             => $playerAction['playerName'],
                'action_id'        => $playerAction['action_id'],
                'action_name'      => $actionName,
                'player_id'        => $playerAction['player_id'],
                'table_seat_id'    => $playerAction['table_seat_id'],
                'hand_street_id'   => $playerAction['hand_street_id'],
                'bet_amount'       => $playerAction['bet_amount'],
                'active'           => $playerAction['active'],
                'can_continue'     => $playerAction['can_continue'],
                'is_dealer'        => $playerAction['is_dealer'],
                'big_blind'        => $playerAction['big_blind'],
                'small_blind'      => $playerAction['small_blind'],
                'whole_cards'      => $wholeCards,
                'action_on'        => $actionOn,
                'availableOptions' => $actionOn ? $this->getAvailableOptionsBasedOnLatestAction($playerAction) : []
            ];
        }

        return $playerData;
    }

    private function getActionOn()
    {
        $firstActivePlayer = $this->gameState->firstActivePlayer();
        $lastToAct         = $this->gameState->getLatestAction()->table_seat_id;

        if ($this->gameState->isNewStreet()) {
            return $this->getThePlayerActionShouldBeOnForANewStreet($firstActivePlayer);
        }

        $activePlayersAfterLastToAct = array_filter($this->gameState->getActivePlayers(), function ($value) use ($lastToAct) {
                return $value['table_seat_id'] > $lastToAct;
        });

        $playerAfterLastToAct = count($activePlayersAfterLastToAct) ? array_shift($activePlayersAfterLastToAct) : null;

        return $playerAfterLastToAct ?: $firstActivePlayer;
    }

    private function getThePlayerActionShouldBeOnForANewStreet(array $firstActivePlayer)
    {
        $dealer            = $this->gameState->getHand()->getDealer();
        $playerAfterDealer = TableSeat::playerAfterDealer($this->gameState->handId(), $dealer->table_seat_id);

        if (!isset($playerAfterDealer->player_id)) { $playerAfterDealer = null; }

        return $playerAfterDealer ? $playerAfterDealer->content[0] : $firstActivePlayer;
    }

    private function getAvailableOptionsBasedOnLatestAction($playerAction)
    {
        if ($this->gameState->isNewStreet()) { return [Action::FOLD, Action::CHECK, Action::BET]; }

        /** BB is the only player that can fold / check / raise pre-flop */
        if (count($this->gameState->getHandStreets()->content) === 1 && !$playerAction['big_blind']) {
            return [Action::FOLD, Action::CALL, Action::RAISE];
        }

        $latestAction      = $this->gameState->getLatestAction();
        $continuingBetters = TableSeat::getContinuingBetters($this->gameState->getHand()->id);

        switch($latestAction->action_id){
            case Action::CALL['id']:
                /** BB can only check if there were no raises before the latest call action. */
                if ($playerAction['big_blind'] && !$this->gameState->getHand()->actions()->search('action_id', Action::RAISE['id'])) {
                    return [Action::FOLD, Action::CHECK, Action::RAISE];
                } else {
                    return [Action::FOLD, Action::CALL, Action::RAISE];
                }
                break;
            case Action::BET['id']:
            case Action::RAISE['id']:
                return [Action::FOLD, Action::CALL, Action::RAISE];
                break;
            case Action::CHECK['id']:
                return [Action::FOLD, Action::CHECK, Action::BET];
                break;
            default:
                /** Latest action may be a fold, so we need to check for raisers/callers/bettters before the folder. */
                if (0 < count($continuingBetters)) { return [Action::FOLD, Action::CALL, Action::RAISE]; break; }

                return [Action::FOLD, Action::CHECK, Action::BET];
                break;
        }
    }
}