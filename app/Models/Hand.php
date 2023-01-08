<?php

namespace Atsmacode\PokerGame\Models;

use Atsmacode\Framework\Collection\Collection;
use Atsmacode\Framework\Dbal\Model;

class Hand extends Model
{
    use Collection;

    protected     $table = 'hands';
    public    int $id;
    public    int $table_id;
    public    ?int $game_type_id;
    public    ?string $completed_on;

    public function streets(): array
    {
        try {
            $queryBuilder = $this->connection->createQueryBuilder();
            $queryBuilder
                ->select('hs.*')
                ->from('hand_streets', 'hs')
                ->leftJoin('hs', 'hands', 'h', 'hs.hand_id = h.id')
                ->where('hs.hand_id = ' . $queryBuilder->createNamedParameter($this->id));

            return $queryBuilder->executeStatement() ? $queryBuilder->fetchAllAssociative() : [];
        } catch(\Exception $e) {
            error_log(__METHOD__ . ': ' . $e->getMessage());
        }
    }

    public function pot(): array
    {
        try {
            $queryBuilder = $this->connection->createQueryBuilder();
            $queryBuilder
                ->select('p.*')
                ->from('pots', 'p')
                ->leftJoin('p', 'hands', 'h', 'p.hand_id = h.id')
                ->where('p.hand_id = ' . $queryBuilder->createNamedParameter($this->id));

            return $queryBuilder->executeStatement() ? $queryBuilder->fetchAssociative() : [];
        } catch(\Exception $e) {
            error_log(__METHOD__ . ': ' . $e->getMessage());
        }
    }

    public function complete(): int
    {
        try {
            $queryBuilder = $this->connection->createQueryBuilder();
            $queryBuilder
                ->update('hands')
                ->set('completed_on', 'NOW()')
                ->where('id = ' . $queryBuilder->createNamedParameter($this->id));

            return $queryBuilder->executeStatement();
        } catch(\Exception $e) {
            error_log(__METHOD__ . ': ' . $e->getMessage());
        }
    }

    public function latest(): self
    {
        $query = sprintf("
            SELECT * FROM hands ORDER BY id DESC LIMIT 1
        ");

        try {
            /**
             * @todo Using query builder here returns no results and causes:
             * SQLSTATE[HY000]: General error: 2014 Cannot execute queries while
             * other unbuffered queries are active.
             */
            // $queryBuilder = $this->connection->createQueryBuilder();
            // $queryBuilder
            //     ->select('*')
            //     ->from('hands')
            //     ->orderBy('id', 'DESC')
            //     ->setMaxResults(1);

            // $rows = $queryBuilder->executeStatement() ? $queryBuilder->fetchAllAssociative() : [];

            // $this->setModelProperties($rows);

            // return $this;
            $stmt    = $this->connection->prepare($query);
            $results = $stmt->executeQuery();
            $rows    = $results->fetchAllAssociative();

            $this->setModelProperties($rows);

            return $this;
        } catch(\Exception $e) {
            error_log(__METHOD__ . ': ' . $e->getMessage());
        }
    }

    public function getDealer(): self
    {
        try {
            $queryBuilder = $this->connection->createQueryBuilder();
            $queryBuilder
                ->select('pa.*')
                ->from('player_actions', 'pa')
                ->leftJoin('pa', 'table_seats', 'ts', 'pa.table_seat_id = ts.id')
                ->where('pa.hand_id = ' . $queryBuilder->createNamedParameter($this->id))
                ->andWhere('ts.is_dealer = 1');

            $rows = $queryBuilder->executeStatement() ? $queryBuilder->fetchAllAssociative() : [];

            $this->setModelProperties($rows);

            return $this;
        } catch(\Exception $e) {
            error_log(__METHOD__ . ': ' . $e->getMessage());
        }
    }

    public function getPlayers(int $handId): array
    {
        try {
            $queryBuilder = $this->connection->createQueryBuilder();
            $queryBuilder
                ->select(
                    'ts.can_continue',
                    'ts.is_dealer',
                    'ts.player_id',
                    'ts.table_id',
                    'pa.bet_amount',
                    'pa.active',
                    'pa.has_acted',
                    'pa.big_blind',
                    'pa.small_blind',
                    'pa.action_id',
                    'pa.hand_id',
                    'pa.hand_street_id',
                    'pa.id player_action_id',
                    'ts.id table_seat_id',
                    's.amount stack',
                    'a.name actionName',
                    'p.name playerName'
                )
                ->from('table_seats', 'ts')
                ->leftJoin('ts', 'player_actions', 'pa', 'ts.id = pa.table_seat_id')
                ->leftJoin('pa', 'players', 'p', 'pa.player_id = p.id')
                ->leftJoin('pa', 'stacks', 's', 'pa.player_id = s.player_id AND ts.table_id = s.table_id')
                ->leftJoin('pa', 'actions', 'a', 'pa.action_id = a.id')
                ->where('pa.hand_id = ' . $queryBuilder->createNamedParameter($handId))
                ->orderBy('ts.id', 'ASC');

            return $queryBuilder->executeStatement() ? $queryBuilder->fetchAllAssociative() : [];
        } catch(\Exception $e) {
            error_log(__METHOD__ . ': ' . $e->getMessage());
        }
    }

    public function getCommunityCards(int $handId = null): array
    {
        $handId = $handId ?? $this->id;

        try {
            $queryBuilder = $this->connection->createQueryBuilder();
            $queryBuilder
                ->select(
                    'c.*',
                    'r.name rank',
                    'r.abbreviation rankAbbreviation',
                    's.name suit',
                    's.abbreviation suitAbbreviation',
                    'r.ranking ranking '
                )
                ->from('hand_street_cards', 'hsc')
                ->leftJoin('hsc', 'hand_streets', 'hs', 'hsc.hand_street_id = hs.id')
                ->leftJoin('hs', 'hands', 'h', 'hs.hand_id = h.id')
                ->leftJoin('hsc', 'cards', 'c', 'hsc.card_id = c.id')
                ->leftJoin('c', 'ranks', 'r', 'c.rank_id = r.id')
                ->leftJoin('c', 'suits', 's', 'c.suit_id = s.id')
                ->where('h.id = ' . $queryBuilder->createNamedParameter($handId))
                ->orderBy('hsc.id', 'ASC');

            return $queryBuilder->executeStatement() ? $queryBuilder->fetchAllAssociative() : [];
        } catch(\Exception $e) {
            error_log(__METHOD__ . ': ' . $e->getMessage());
        }
    }
}
