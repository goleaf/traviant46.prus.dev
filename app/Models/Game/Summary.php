<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Summary extends Model
{
    use HasFactory;

    protected $table = 'summary';

    public $timestamps = false;

    protected $fillable = [
        'players_count',
        'roman_players_count',
        'teuton_players_count',
        'gaul_players_count',
        'egyptians_players_count',
        'huns_players_count',
    ];
}
