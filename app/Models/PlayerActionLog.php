<?php

namespace Atsmacode\PokerGame\Models;

class PlayerActionLog extends Model
{
    use Collection;

    protected $table = 'player_action_logs';
    public $id;
}
