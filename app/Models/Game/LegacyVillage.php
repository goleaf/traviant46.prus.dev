<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LegacyVillage extends Model
{
    use HasFactory;

    protected $table = 'vdata';

    protected $primaryKey = 'kid';

    public $timestamps = false;

    protected $fillable = [
        'kid',
        'owner',
        'fieldtype',
        'name',
        'capital',
        'pop',
        'cp',
        'wood',
        'clay',
        'iron',
        'crop',
        'maxstore',
        'maxcrop',
        'last_loyalty_update',
        'lastmupdate',
        'created',
        'isWW',
        'expandedfrom',
        'lastVillageCheck',
    ];

    protected $casts = [
        'kid' => 'integer',
        'owner' => 'integer',
        'fieldtype' => 'integer',
        'capital' => 'boolean',
        'pop' => 'integer',
        'cp' => 'integer',
        'wood' => 'integer',
        'clay' => 'integer',
        'iron' => 'integer',
        'crop' => 'integer',
        'maxstore' => 'integer',
        'maxcrop' => 'integer',
        'last_loyalty_update' => 'integer',
        'lastmupdate' => 'integer',
        'created' => 'integer',
        'isWW' => 'boolean',
        'expandedfrom' => 'integer',
        'lastVillageCheck' => 'integer',
    ];
}
