<?php

namespace Tests\Feature\Controllers;

use App\Classes\ActionHandler\ActionHandler;
use App\Classes\GamePlay\GamePlay;
use App\Classes\GameState\GameState;
use App\Constants\Action;
use App\Controllers\PlayerActionController;
use App\Models\Hand;
use App\Models\Player;
use App\Models\Table;
use App\Models\TableSeat;
use Tests\BaseTest;
use Tests\Feature\GamePlay\HasGamePlay;

class GamePlayActionOptionsTest extends BaseTest
{
    use HasGamePlay;
    use HasActionPosts;

    protected function setUp(): void
    {
        parent::setUp();

        $this->table         = Table::create(['name' => 'Test Table', 'seats' => 3]);
        $this->gamePlay      = new GamePlay(Hand::create(['table_id' => $this->table->id]));
        $this->gameState     = new GameState();
        $this->actionHandler = new ActionHandler($this->gameState);

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

        $this->player4 = Player::create([
            'name' => 'Player 4',
            'email' => 'player4@rrh.com'
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

        TableSeat::create([
            'table_id' => $this->gamePlay->handTable->id,
            'player_id' => $this->player4->id
        ]); 
    }

    /**
     * @test
     * @return void
     */
    public function a_player_facing_a_raise_can_fold_call_or_raise()
    {
        $this->gamePlay->start(null, $this->gameState);

        $this->setPlayerFourRaisesPost();

        $response     = (new PlayerActionController($this->actionHandler))->action();
        $responseBody = json_decode($response, true)['body'];

        $this->assertTrue($responseBody['players'][0]['action_on']);

        $this->assertContains(Action::FOLD, $responseBody['players'][0]['availableOptions']);
        $this->assertContains(Action::CALL, $responseBody['players'][0]['availableOptions']);
        $this->assertContains(Action::RAISE, $responseBody['players'][0]['availableOptions']);
    }

    /**
     * @test
     * @return void
     */
    public function a_player_facing_a_raise_fold_can_fold_call_or_raise()
    {
        $this->gamePlay->start(null, $this->gameState);

        $this->givenPlayerFourRaises();
        $this->setPlayerOneFoldsPost();

        $response     = (new PlayerActionController($this->actionHandler))->action();
        $responseBody = json_decode($response, true)['body'];

        $this->assertTrue($responseBody['players'][1]['action_on']);

        $this->assertContains(Action::FOLD, $responseBody['players'][1]['availableOptions']);
        $this->assertContains(Action::CALL, $responseBody['players'][1]['availableOptions']);
        $this->assertContains(Action::RAISE, $responseBody['players'][1]['availableOptions']);
    }

    /**
     * @test
     * @return void
     */
    public function a_folded_player_has_no_options()
    {
        $this->gamePlay->start(null, $this->gameState);

        $this->setPlayerFourFoldsPost();

        $response     = (new PlayerActionController($this->actionHandler))->action();
        $responseBody = json_decode($response, true)['body'];

        $this->assertTrue($responseBody['players'][0]['action_on']);
        $this->assertEmpty($responseBody['players'][3]['availableOptions']);
    }

    /**
     * @test
     * @return void
     */
    public function the_big_blind_facing_a_call_can_fold_check_or_raise()
    {
        $this->gamePlay->start(null, $this->gameState);

        $this->setPlayerTwoCallsPost();

        $response     = (new PlayerActionController($this->actionHandler))->action();
        $responseBody = json_decode($response, true)['body'];

        $this->assertTrue($responseBody['players'][2]['action_on']);

        $this->assertContains(ACTION::FOLD, $responseBody['players'][2]['availableOptions']);
        $this->assertContains(ACTION::CHECK, $responseBody['players'][2]['availableOptions']);
        $this->assertContains(ACTION::RAISE, $responseBody['players'][2]['availableOptions']);
    }

    /**
     * @test
     * @return void
     */
    public function a_player_facing_a_call_can_fold_call_or_raise()
    {
        
        $this->gamePlay->start(null, $this->gameState);

        $this->setPlayerFourCallsPost();

        $response     = (new PlayerActionController($this->actionHandler))->action();
        $responseBody = json_decode($response, true)['body'];

        $this->assertTrue($responseBody['players'][0]['action_on']);

        $this->assertContains(ACTION::FOLD, $responseBody['players'][0]['availableOptions']);
        $this->assertContains(ACTION::CALL, $responseBody['players'][0]['availableOptions']);
        $this->assertContains(ACTION::RAISE, $responseBody['players'][0]['availableOptions']);
    }

    /**
     * TODO: the first active player on a new street can fold, check or bet
     */
}
