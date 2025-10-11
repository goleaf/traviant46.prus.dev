<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Model;

class Movement extends Model
{
    protected $table = 'movement';

    protected $connection = 'legacy';

    public $timestamps = false;

    protected $fillable = [
        'kid',
        'to_kid',
        'race',
        'u1',
        'u2',
        'u3',
        'u4',
        'u5',
        'u6',
        'u7',
        'u8',
        'u9',
        'u10',
        'u11',
        'ctar1',
        'ctar2',
        'spyType',
        'redeployHero',
        'mode',
        'attack_type',
        'start_time',
        'end_time',
        'data',
        'markState',
        'proc',
    ];

    protected $casts = [
        'kid' => 'int',
        'to_kid' => 'int',
        'race' => 'int',
        'u1' => 'int',
        'u2' => 'int',
        'u3' => 'int',
        'u4' => 'int',
        'u5' => 'int',
        'u6' => 'int',
        'u7' => 'int',
        'u8' => 'int',
        'u9' => 'int',
        'u10' => 'int',
        'u11' => 'int',
        'ctar1' => 'int',
        'ctar2' => 'int',
        'spyType' => 'int',
        'redeployHero' => 'bool',
        'mode' => 'int',
        'attack_type' => 'int',
        'start_time' => 'int',
        'end_time' => 'int',
        'data' => 'string',
        'markState' => 'int',
        'proc' => 'int',
    ];
}
