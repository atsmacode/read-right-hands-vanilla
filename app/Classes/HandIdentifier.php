<?php

namespace App\Classes;

use App\Helpers\QueryHelper;
use App\Models\HandType;

class HandIdentifier
{

    public $handTypes;
    public $identifiedHandType = [
        'handType' => null,
        'activeCards' => [0],
        'kicker' => null
    ];
    public $allCards;
    public $highCard;
    public $pairs = [];
    public $threeOfAKind = false;
    public $straight = false;
    public $flush = false;
    public $fullHouse = false;
    public $fourOfAKind = false;
    public $straightFlush = false;
    public $royalFlush = false;
    protected $handMethods = [
        /*'hasRoyalFlush',
        'hasStraightFlush',
        'hasFourOfAKind',
        'hasFullHouse',
        'hasFlush',
        'hasStraight',
        'hasThreeOfAKind',*/
        'hasTwoPair',
        'hasPair',
        'highestCard'
    ];

    public function __construct()
    {
        $this->handTypes = (new HandType())->all()->collect()->content;
    }

    public function identify($wholeCards, $communityCards)
    {
        $this->allCards = array_merge($wholeCards, $communityCards);

        foreach ($this->handMethods as $handMethod) {
            if ($this->{$handMethod}() === true) {
                break;
            }
        }

        return $this;
    }

    private function checkForAceKicker($forHandCheck, $activeCards = null)
    {
        if ($this->thereIsNoAceInTheActiveCardsUnlessHandIsFlush($forHandCheck, $activeCards)) {
            return 14;
        }

        return false;
    }

    /**
     * Ace is technically ranked 1 in the DB, but because it can be
     * used high or low, we need to switch it to 14 so it can be
     * ranked higher than a king (13) if required.
     *
     * @param array<mixed> $rank
     * @return int|bool
     */
    private function checkForHighAceActiveCardRanking($rank)
    {
        if ($rank['ranking'] === 1) {
            return 14;
        }

        return false;
    }

    protected function thereIsNoAceInTheActiveCardsUnlessHandIsFlush($forHandCheck, $activeCards)
    {
        return ($activeCards && count($this->filter('allCards', 'ranking', 1)) > 1
                && !in_array(1, $activeCards)
                && !in_array(14, $activeCards))
            || (in_array(1, $activeCards) && $forHandCheck === 'hasFlush');
    }

    private function getMax($haystack, $columm)
    {
        return max(array_column($haystack, $columm));
    }

    private function getMin($haystack, $columm)
    {
        return min(array_column($haystack, $columm));
    }

    private function getKicker($highestActiveCard = null)
    {
        $cardRankings = array_column($this->sortCardsByDescRanking(), 'ranking');

        foreach ($cardRankings as $cardRankings) {
            if(($this->highCard && $cardRankings != $this->highCard) || $cardRankings != $highestActiveCard){
                return $cardRankings;
            }
        }
    }

    private function search($hayStack, $column, $value)
    {
        $key = array_search($value,
            array_column($this->{$hayStack}, $column)
        );

        if(array_key_exists($key, $this->{$hayStack})){
            return $this->{$hayStack}[$key];
        }

        return false;
    }

    private function filter($hayStack, $column, $filter)
    {
        return array_filter($this->{$hayStack}, function($value) use($column, $filter){
            return $value->{$column} === $filter;
        });
    }

    private function sortCardsByDescRanking()
    {
        uasort($this->allCards, function ($a, $b){
            if ($a->ranking == $b->ranking) {
                return 0;
            }
            return ($a->ranking > $b->ranking) ? -1 : 1;
        });

        return $this->allCards;
    }

    public function highestCard()
    {
        if ($this->getMin($this->allCards, 'ranking') === 1) {
            $this->highCard = 14;
        } else {
            $this->highCard = $this->getMax($this->allCards, 'ranking');
        }

        $this->identifiedHandType['handType']      = $this->search('handTypes', 'name', 'High Card');
        $this->identifiedHandType['activeCards'][] = $this->highCard;
        $this->identifiedHandType['kicker']        = $this->getKicker();

        return $this;
    }

    public function hasPair()
    {
        foreach (QueryHelper::selectRanks() as $rank) {
            if (count($this->filter('allCards', 'rank_id', $rank['id'])) === 2) {
                $this->pairs[] = $rank;
                $this->identifiedHandType['activeCards'][] = $this->checkForHighAceActiveCardRanking($rank) ?: $rank['ranking'];
                /*
                 * The showdown may be called pre-flop when the pot is checked down to BB.
                 * In which case they may have a pair and no other kicker rank.
                 * Ultimately this will be handled more elegantly when kickers are fully fleshed out.
                 */
                if (count($this->allCards) > 2) {
                    $this->identifiedHandType['kicker'] = $this->checkForAceKicker(__FUNCTION__,  $this->identifiedHandType['activeCards'])
                        ?: $this->getKicker($rank['ranking']);
                } else {
                    $this->identifiedHandType['kicker'] = $rank['ranking'];
                }
            }
        }

        if (count($this->pairs) === 1) {
            $this->identifiedHandType['handType'] = $this->search('handTypes', 'name', 'Pair');
            return true;
        }

        return $this;
    }

    public function hasTwoPair()
    {
        foreach(QueryHelper::selectRanks() as $rank){
            if (count($this->filter('allCards', 'rank_id', $rank['id'])) === 2) {
                $this->pairs[]                             = $rank;
                $this->identifiedHandType['activeCards'][] = $this->checkForHighAceActiveCardRanking($rank) ?: $rank['ranking'];
                /*
                 * The showdown may be called pre-flop when the pot is checked down to BB.
                 * In which case they may have a pair and no other kicker rank.
                 * Ultimately this will be handled more elegantly when kickers are fully fleshed out.
                 */
                if(count($this->allCards) > 2){
                    $this->identifiedHandType['kicker'] = $this->checkForAceKicker(__FUNCTION__,  $this->identifiedHandType['activeCards'])
                        ?: $this->getKicker($rank['ranking']);
                } else {
                    $this->identifiedHandType['kicker'] = $rank['ranking'];
                }
            }
        }

        if (count($this->pairs) >= 2) {
            $this->identifiedHandType['handType'] = $this->search('handTypes', 'name', 'Two Pair');
            return true;
        }

        $this->pairs = [];

        return $this;
    }
}
