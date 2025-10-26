<?php

declare(strict_types=1);

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Model;

class WorldCasualtySnapshot extends Model
{
    protected $table = 'world_casualty_snapshots';

    protected $fillable = [
        'attack_count',
        'casualty_count',
        'recorded_at',
    ];

    protected $casts = [
        'attack_count' => 'int',
        'casualty_count' => 'int',
        'recorded_at' => 'datetime',
    ];
}
