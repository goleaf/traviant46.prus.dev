<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAccount extends Model
{
    use HasFactory;

    protected $table = 'users';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'name',
        'password',
        'email',
        'access',
        'gift_gold',
        'signupTime',
        'protection',
        'race',
        'kid',
        'cp',
        'lastupdate',
        'last_adventure_time',
        'location',
        'desc1',
        'desc2',
        'note',
        'last_login_time',
        'countryFlag',
        'lastCountryFlagCheck',
        'profileCacheVersion',
        'total_villages',
        'total_pop',
        'cp_prod',
    ];

    protected $casts = [
        'access' => 'integer',
        'gift_gold' => 'integer',
        'signupTime' => 'integer',
        'protection' => 'integer',
        'race' => 'integer',
        'kid' => 'integer',
        'cp' => 'integer',
        'lastupdate' => 'integer',
        'last_adventure_time' => 'integer',
        'last_login_time' => 'integer',
        'lastCountryFlagCheck' => 'integer',
        'profileCacheVersion' => 'integer',
        'total_villages' => 'integer',
        'total_pop' => 'integer',
        'cp_prod' => 'integer',
    ];
}
