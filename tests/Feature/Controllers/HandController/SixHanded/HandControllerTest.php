<?php

namespace Atsmacode\PokerGame\Tests\Feature\Controllers\HandController\SixHanded;

use Atsmacode\PokerGame\Controllers\PotLimitHoldEm\HandController as PotLimitHoldEmHandController;
use Atsmacode\PokerGame\Models\TableSeat;
use Atsmacode\PokerGame\Tests\BaseTest;
use Atsmacode\PokerGame\Tests\HasActionPosts;
use Atsmacode\PokerGame\Tests\HasGamePlay;

class HandControllerTest extends BaseTest
{
    use HasGamePlay, HasActionPosts;

    protected function setUp(): void
    {
        parent::setUp();

        $this->isSixHanded()
;    }

    /**
     * @test
     * @return void
     */
    public function the_pre_flop_action_will_initially_be_on_player_four()
    {
        $response = $this->jsonResponse();

        $this->assertTrue($response['players'][3]['action_on']);
    }

    /**
     * @test
     * @return void
     */
    public function if_there_are_two_seats_after_current_dealer_big_blind_will_be_seat_one()
    {
        $currentDealer = $this->tableSeatFour;

        $response = $this->jsonResponse($currentDealer);

        $this->assertEquals(1, $response['players'][5]['small_blind']);
        $this->assertEquals(1, $response['players'][0]['big_blind']);
    }

    /**
     * @test
     * @return void
     */
    public function if_there_is_one_seat_after_current_dealer_big_blind_will_be_seat_two()
    {
        $currentDealer = $this->tableSeatFive;

        $response = $this->jsonResponse($currentDealer);

        $this->assertEquals(1, $response['players'][0]['small_blind']);
        $this->assertEquals(1, $response['players'][1]['big_blind']);
    }

    private function jsonResponse(TableSeat $currentDealer = null): array
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $response = (new PotLimitHoldEmHandController($this->container))->play($this->table->id, $currentDealer);

        return json_decode($response->getBody()->getContents(), true);
    }
}
