<?php

namespace Tests\Unit;

use App\Classes\HandIdentifier;
use App\Constants\Card as ConstantsCard;
use App\Models\Card;

class HandIdentifierTest extends BaseTest
{

    protected function setUp(): void
    {
        parent::setUp();
        $this->handIdentifier = new HandIdentifier();
    }

    /**
     * @test
     * @return void
     */
    public function it_can_identify_the_card_with_the_highest_rank()
    {
        $highestCard = new Card(ConstantsCard::KING_SPADES);

        $wholeCards = [
            new Card(ConstantsCard::DEUCE_SPADES),
            $highestCard,
        ];

        $communityCards = [
            new Card(ConstantsCard::QUEEN_HEARTS),
            new Card(ConstantsCard::SEVEN_DIAMONDS),
            new Card(ConstantsCard::TEN_CLUBS),
            new Card(ConstantsCard::THREE_SPADES),
            new Card(ConstantsCard::FOUR_DIAMONDS),
        ];

        $this->handIdentifier->identify($wholeCards, $communityCards);

        $this->assertEquals('High Card', $this->handIdentifier->identifiedHandType['handType']->name);

        $this->assertEquals(
            $highestCard->ranking,
            $this->handIdentifier->highCard
        );
    }

    /**
     * @test
     * @return void
     */
    public function it_can_identify_an_ace_as_the_card_with_the_highest_rank()
    {
        $highestCard = new Card(ConstantsCard::ACE_SPADES);

        $wholeCards = [
            $highestCard,
            new Card(ConstantsCard::KING_DIAMONDS)
        ];

        $communityCards = [
            new Card(ConstantsCard::QUEEN_CLUBS),
            new Card(ConstantsCard::FOUR_SPADES),
            new Card(ConstantsCard::TEN_DIAMONDS),
            new Card(ConstantsCard::DEUCE_CLUBS),
            new Card(ConstantsCard::EIGHT_CLUBS),
        ];

        $this->handIdentifier->identify($wholeCards, $communityCards);

        $this->assertEquals('High Card', $this->handIdentifier->identifiedHandType['handType']->name);

        $this->assertEquals(
            14,
            $this->handIdentifier->highCard
        );
    }

    /**
     * @test
     * @return void
     */
    public function it_can_identify_a_pair()
    {
        $wholeCards = [
            new Card(ConstantsCard::ACE_CLUBS),
            new Card(ConstantsCard::KING_DIAMONDS),
        ];

        $communityCards = [
            new Card(ConstantsCard::ACE_HEARTS),
            new Card(ConstantsCard::JACK_DIAMONDS),
            new Card(ConstantsCard::FOUR_DIAMONDS),
            new Card(ConstantsCard::NINE_CLUBS),
            new Card(ConstantsCard::SEVEN_DIAMONDS),
        ];

        $this->handIdentifier->identify($wholeCards, $communityCards);
        $this->assertEquals('Pair', $this->handIdentifier->identifiedHandType['handType']->name);
        $this->assertCount(1, $this->handIdentifier->pairs);
    }

    /**
     * @test
     * @return void
     */
    public function it_can_identify_two_pair()
    {
        $wholeCards = [
            new Card(ConstantsCard::ACE_SPADES),
            new Card(ConstantsCard::KING_SPADES),
        ];

        $communityCards = [
            new Card(ConstantsCard::ACE_HEARTS),
            new Card(ConstantsCard::KING_HEARTS),
            new Card(ConstantsCard::TEN_DIAMONDS),
            new Card(ConstantsCard::NINE_CLUBS),
            new Card(ConstantsCard::EIGHT_DIAMONDS),
        ];

        $this->handIdentifier->identify($wholeCards, $communityCards);
        $this->assertEquals('Two Pair', $this->handIdentifier->identifiedHandType['handType']->name);
        $this->assertCount(2, $this->handIdentifier->pairs);
    }
}
