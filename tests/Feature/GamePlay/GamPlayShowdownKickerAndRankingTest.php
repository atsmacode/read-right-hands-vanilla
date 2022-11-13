<?php

namespace Tests\Feature\GamePlay;

use App\Classes\GamePlay\GamePlay;
use App\Constants\Card as ConstantsCard;
use App\Models\Hand;
use App\Models\HandStreet;
use App\Models\HandStreetCard;
use App\Models\HandType;
use App\Models\Player;
use App\Models\Street;
use App\Models\Table;
use App\Models\TableSeat;
use App\Models\WholeCard;
use Tests\BaseTest;

class GamePlayShowdownKickerAndRankingTest extends BaseTest
{
    use HasGamePlay;

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

        $this->executeActionsToContinue();

        $gamePlay = $this->gamePlay->play();

        $this->assertEquals($this->player3->id, $gamePlay['winner']['player']->id);
        $this->assertEquals($this->handTypes->find(['name' => 'High Card'])->id, $gamePlay['winner']['handType']->id);
    }

    /**
     * @test
     * @return void
     */
    public function ace_king_beats_king_queen()
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
                'card_id' => ConstantsCard::ACE_DIAMONDS_ID
            ],
            [
                'player'  => $this->player1,
                'card_id' => ConstantsCard::QUEEN_SPADES_ID
            ],
            [
                'player'  => $this->player1,
                'card_id' => ConstantsCard::KING_DIAMONDS_ID
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
            'card_id' => ConstantsCard::THREE_HEARTS_ID
        ];

        $this->setRiver($riverCard);

        $this->executeActionsToContinue();

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
}
