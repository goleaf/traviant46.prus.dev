<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnitStack extends Model
{
    use HasFactory;

    protected $table = 'units';

    public $timestamps = false;

    protected $fillable = [
        'kid',
        'race',
        'u11',
    ];

    protected $casts = [
        'kid' => 'integer',
        'race' => 'integer',
        'u11' => 'integer',
    ];
}
