<?php

namespace Atsmacode\PokerGame\Tests\Feature\Controllers\PlayerActionController\HoldEmStreetTest\SixHanded;

use Atsmacode\PokerGame\Tests\BaseTest;
use Atsmacode\PokerGame\Tests\HasActionPosts;
use Atsmacode\PokerGame\Tests\HasGamePlay;
use Atsmacode\PokerGame\Tests\HasStreets;

class PlayerActionControllerTest extends BaseTest
{
    use HasGamePlay, HasActionPosts, HasStreets;

    protected function setUp(): void
    {
        parent::setUp();

        $this->isSixHanded();
    }

     /**
     * @test
     * @return void
     */
    public function whenDealerIsSeatSixAndOnlySmallBlindCallsAndBigBlindChecksItCanDealFlop()
    {
        $this->gamePlay->start($this->tableSeatSix);

        $this->givenPlayerThreeFolds();
        $this->givenPlayerThreeCanNotContinue();

        $this->givenPlayerFourFolds();
        $this->givenPlayerFourCanNotContinue();

        $this->givenPlayerFiveFolds();
        $this->givenPlayerFiveCanNotContinue();

        $this->givenPlayerSixFolds();
        $this->givenPlayerSixCanNotContinue();

        $this->givenPlayerOneCalls();
        $this->givenPlayerOneCanContinue();

        $this->setPlayerTwoChecksPost();

        $this->actionControllerResponse();

        $this->assertCount(2, $this->handStreetModel->find(['hand_id' => $this->gameState->handId()])->content);
        $this->assertCount(3, $this->handStreetModel->getStreetCards($this->gameState->handId(), 2));
    }
}
