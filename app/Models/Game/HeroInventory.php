<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HeroInventory extends Model
{
    use HasFactory;

    protected $table = 'inventory';

    public $timestamps = false;

    protected $fillable = [
        'uid',
        'lastupdate',
    ];

    protected $casts = [
        'uid' => 'integer',
        'lastupdate' => 'integer',
    ];
}
