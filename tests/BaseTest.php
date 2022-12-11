<?php

namespace Atsmacode\PokerGame\Tests;

use Atsmacode\Framework\Database\ConnectionInterface;
use Atsmacode\PokerGame\Database\DbalTestFactory;
use Atsmacode\PokerGame\Dealer\PokerDealer;
use Atsmacode\PokerGame\Factory\PlayerActionFactory;
use Atsmacode\PokerGame\Models\Hand;
use Atsmacode\PokerGame\Models\HandStreet;
use Atsmacode\PokerGame\Models\Player;
use Atsmacode\PokerGame\Models\Street;
use Atsmacode\PokerGame\Models\Table;
use Atsmacode\PokerGame\Models\TableSeat;
use Atsmacode\PokerGame\Models\WholeCard;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;

abstract class BaseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $pokerGameDependencyMap  = require('config/dependencies.php');

        $this->container = new ServiceManager($pokerGameDependencyMap);
        $this->container->setFactory(ConnectionInterface::class, new DbalTestFactory());

        $this->tableModel          = $this->container->build(Table::class);
        $this->handModel           = $this->container->build(Hand::class);
        $this->playerModel         = $this->container->build(Player::class);
        $this->tableSeatModel      = $this->container->build(TableSeat::class);
        $this->handStreetModel     = $this->container->build(HandStreet::class);
        $this->playerActionFactory = $this->container->build(PlayerActionFactory::class);
        $this->wholeCardModel      = $this->container->build(WholeCard::class);
        $this->streetModel         = $this->container->build(Street::class);
        $this->pokerDealer         = $this->container->build(PokerDealer::class);
    }
}
