<?php

namespace Tests\Unit;

use App\Classes\GamePlay;
use App\Constants\Action;
use App\Constants\Card as ConstantsCard;
use App\Models\Hand;
use App\Models\HandStreet;
use App\Models\HandStreetCard;
use App\Models\HandType;
use App\Models\Player;
use App\Models\PlayerAction;
use App\Models\Street;
use App\Models\Table;
use App\Models\TableSeat;
use App\Models\WholeCard;

class ShowdownKickerAndRankingTest extends BaseTest
{

    protected function setUp(): void
    {
        parent::setUp();

        $this->table    = Table::create(['name' => 'Test Table', 'seats' => 3]);
        $this->gamePlay = new GamePlay(Hand::create(['table_id' => $this->table->id]));

        $this->player1 = Player::create([
            'name' => 'Player 1',
            'email' => 'player1@rrh.com'
        ]);

        $this->player2 = Player::create([
            'name' => 'Player 2',
            'email' => 'player2@rrh.com'
        ]);

        $this->player3 = Player::create([
            'name' => 'Player 3',
            'email' => 'player3@rrh.com'
        ]);

        TableSeat::create([
            'table_id' => $this->gamePlay->handTable->id,
            'player_id' => $this->player1->id
        ]);

        TableSeat::create([
            'table_id' => $this->gamePlay->handTable->id,
            'player_id' => $this->player2->id
        ]);

        TableSeat::create([
            'table_id' => $this->gamePlay->handTable->id,
            'player_id' => $this->player3->id
        ]); 

        $this->handTypes = (new HandType())->all();
    }

    /**
     * @test
     * @return void
     */
    public function high_card_king_beats_high_card_queen()
    {
        $this->gamePlay->initiateStreetActions()
            ->initiatePlayerStacks()
            ->setDealerAndBlindSeats();

        $wholeCards = [
            [
                'player'  => $this->player3,
                'card_id' => ConstantsCard::KING_SPADES_ID
            ],
            [
                'player'  => $this->player3,
                'card_id' => ConstantsCard::THREE_DIAMONDS_ID
            ],
            [
                'player'  => $this->player1,
                'card_id' => ConstantsCard::QUEEN_SPADES_ID
            ],
            [
                'player'  => $this->player1,
                'card_id' => ConstantsCard::SEVEN_DIAMONDS_ID
            ],
        ];

        $this->setWholeCards($wholeCards);

        $flopCards = [
            [
                'card_id' => ConstantsCard::FOUR_CLUBS_ID
            ],
            [
                'card_id' => ConstantsCard::JACK_SPADES_ID
            ],
            [
                'card_id' => ConstantsCard::DEUCE_CLUBS_ID
            ]
        ];

        $this->setFlop($flopCards);

        $turnCard = [
            'card_id' => ConstantsCard::NINE_DIAMONDS_ID
        ];

        $this->setTurn($turnCard);

        $riverCard = [
            'card_id' => ConstantsCard::TEN_SPADES_ID
        ];

        $this->setRiver($riverCard);

        $this->executeActions();

        $gamePlay = $this->gamePlay->play();

        $this->assertEquals($this->player3->id, $gamePlay['winner']['player']->id);
        $this->assertEquals($this->handTypes->find(['name' => 'High Card'])->id, $gamePlay['winner']['handType']->id);
    }

    protected function setWholeCards($wholeCards)
    {
        foreach($wholeCards as $card){
            WholeCard::create([
                'player_id' => $card['player']->id,
                'card_id'   => $card['card_id'],
                'hand_id'   => $this->gamePlay->hand->id
            ]);
        }
    }

    protected function setflop($flopCards)
    {
        $flop = HandStreet::create([
            'street_id' => Street::find(['name' => $this->gamePlay->game->streets[1]['name']])->id,
            'hand_id' => $this->gamePlay->hand->id
        ]);

        foreach($flopCards as $card){
            HandStreetCard::create([
                'hand_street_id' => $flop->id,
                'card_id'        => $card['card_id']
            ]);
        }
    }

    protected function setTurn($turnCard)
    {
        $turn = HandStreet::create([
            'street_id' => Street::find(['name' => $this->gamePlay->game->streets[2]['name']])->id,
            'hand_id' => $this->gamePlay->hand->id
        ]);

        HandStreetCard::create([
            'hand_street_id' => $turn->id,
            'card_id'        => $turnCard['card_id']
        ]);
    }

    protected function setRiver($riverCard)
    {
        $river = HandStreet::create([
            'street_id' => Street::find(['name' => $this->gamePlay->game->streets[3]['name']])->id,
            'hand_id' => $this->gamePlay->hand->id
        ]);

        HandStreetCard::create([
            'hand_street_id' => $river->id,
            'card_id'        => $riverCard['card_id']
        ]);
    }

    protected function executeActions()
    {
        // Player 1 Calls BB
        PlayerAction::find(['id' => $this->gamePlay->hand->actions()->slice(0, 1)->id])
            ->update([
                'action_id' => Action::CALL_ID,
                'bet_amount' => 50.0,
                'active' => 1,
                'updated_at' => date('Y-m-d H:i:s', strtotime('-3 seconds'))
            ]);

        TableSeat::find(['id' => $this->gamePlay->handTable->seats()->slice(0, 1)->id])
            ->update([
                'can_continue' => 1
            ]);

        // Player 2 Folds
        PlayerAction::find(['id' => $this->gamePlay->hand->actions()->slice(1, 1)->id])
            ->update([
                'action_id' => Action::FOLD_ID,
                'bet_amount' => null,
                'active' => 0,
                'updated_at' => date('Y-m-d H:i:s', strtotime('-2 seconds'))
            ]);

        TableSeat::find(['id' => $this->gamePlay->handTable->seats()->slice(1, 1)->id])
            ->update([
                'can_continue' => 0
            ]);

        // Player 3 Checks
        PlayerAction::find(['id' => $this->gamePlay->hand->actions()->slice(2, 1)->id])
            ->update([
                'action_id' => Action::CHECK_ID,
                'bet_amount' => null,
                'active' => 1,
                'updated_at' => date('Y-m-d H:i:s', strtotime('-1 seconds'))
            ]);

        TableSeat::find(['id' => $this->gamePlay->handTable->seats()->slice(2, 1)->id])
            ->update([
                'can_continue' => 1
            ]);
    }
}
