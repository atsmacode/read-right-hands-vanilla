<?php

namespace Atsmacode\PokerGame\Models;

use Atsmacode\PokerGame\Constants\Action;
use Atsmacode\Framework\Collection\Collection;
use Atsmacode\Framework\Dbal\Model;

class TableSeat extends Model
{
    protected string $table = 'table_seats';
    private ?int     $number;
    private bool     $can_continue;
    private int      $is_dealer;
    private int      $player_id;
    private int      $table_id;
    private ?string  $updated_at;

    public function canContinue(): bool
    {
        return $this->can_continue;
    }

    public function getPlayerId(): int
    {
        return $this->player_id;
    }

    public function playerAfterDealer(int $handId, int $dealer): self
    {
        try {
            $queryBuilder = $this->connection->createQueryBuilder();
            $queryBuilder
                ->select('ts.*')
                ->from('table_seats', 'ts')
                ->leftJoin('ts', 'player_actions', 'pa', 'ts.id = pa.table_seat_id')
                ->where('pa.hand_id = ' . $queryBuilder->createNamedParameter($handId))
                ->andWhere('ts.id > ' . $queryBuilder->createNamedParameter($dealer))
                ->andWhere('pa.active = 1')
                ->setMaxResults(1);

            $rows = $queryBuilder->executeStatement() ? $queryBuilder->fetchAllAssociative() : [];

            $this->content = $rows;
            
            $this->setModelProperties($rows);

            return $this;
        } catch (\Exception $e) {
            error_log(__METHOD__ . ': ' . $e->getMessage());
        }
    }

    public function bigBlindWins(int $tableSeatId): int
    {
        try {
            $queryBuilder = $this->connection->createQueryBuilder();
            $queryBuilder
                ->update('table_seats')
                ->set('can_continue', 1)
                ->where('id = ' . $queryBuilder->createNamedParameter($tableSeatId));

            return $queryBuilder->executeStatement();
        } catch (\Exception $e) {
            error_log(__METHOD__ . ': ' . $e->getMessage());
        }
    }

    public function getBigBlind(string $handId): array
    {
        try {
            $queryBuilder = $this->connection->createQueryBuilder();
            $queryBuilder
                ->select('pa.*')
                ->from('player_actions', 'pa')
                ->leftJoin('pa', 'table_seats', 'ts', 'pa.table_seat_id = ts.id')
                ->where('pa.hand_id = ' . $queryBuilder->createNamedParameter($handId))
                ->andWhere('pa.big_blind = 1');

            return $queryBuilder->executeStatement() ? $queryBuilder->fetchAssociative() : [];
        } catch (\Exception $e) {
            error_log(__METHOD__ . ': ' . $e->getMessage());
        }
    }

    public function getContinuingPlayerSeats(string $handId): self
    {
        try {
            $queryBuilder = $this->connection->createQueryBuilder();
            $queryBuilder
                ->select('ts.*')
                ->from('player_actions', 'pa')
                ->leftJoin('pa', 'table_seats', 'ts', 'pa.table_seat_id = ts.id')
                ->where('pa.hand_id = ' . $queryBuilder->createNamedParameter($handId))
                ->andWhere('pa.active = 1')
                ->andWhere('ts.can_continue = 1');

            $rows = $queryBuilder->executeStatement() ? $queryBuilder->fetchAllAssociative() : [];

            $this->content = $rows;
            $this->setModelProperties($rows);

            return $this;
        } catch (\Exception $e) {
            error_log(__METHOD__ . ': ' . $e->getMessage());
        }
    }

    public function getContinuingBetters(string $handId): array
    {
        $raiseId = Action::RAISE_ID;
        $betId   = Action::BET_ID;
        $callId  = Action::CALL_ID;

        try {
            $queryBuilder      = $this->connection->createQueryBuilder();
            $expressionBuilder = $this->connection->createExpressionBuilder();

            $queryBuilder
                ->select('ts.*')
                ->from('player_actions', 'pa')
                ->leftJoin('pa', 'table_seats', 'ts', 'pa.table_seat_id = ts.id')
                ->where('pa.hand_id = ' . $queryBuilder->createNamedParameter($handId))
                ->andWhere('ts.can_continue = 1')
                ->andWhere(
                    $expressionBuilder->in(
                        'pa.action_id',
                        [
                            $queryBuilder->createNamedParameter($raiseId),
                            $queryBuilder->createNamedParameter($betId),
                            $queryBuilder->createNamedParameter($callId),
                        ]
                    )
                );

            return $queryBuilder->executeStatement() ? $queryBuilder->fetchAllAssociative() : [];
        } catch (\Exception $e) {
            error_log(__METHOD__ . ': ' . $e->getMessage());
        }
    }
}
