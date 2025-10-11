<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Model;

class Enforcement extends Model
{
    protected $table = 'enforcement';

    protected $connection = 'legacy';

    public $timestamps = false;

    protected $fillable = [
        'uid',
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
    ];

    protected $casts = [
        'uid' => 'int',
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
    ];
}
