<?php

namespace App\Models;

class HandStreet extends Model
{

    use Collection;

    protected $table = 'hand_streets';
    public $id;

    public function cards($stop = false)
    {
        self::__construct($this->data, $stop);

        return HandStreetCard::find(['hand_street_id' => $this->id], $stop);
    }

}