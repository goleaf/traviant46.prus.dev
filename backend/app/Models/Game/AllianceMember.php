<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AllianceMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'alliance_id',
        'user_id',
        'contribution',
    ];

    protected $casts = [
        'contribution' => 'integer',
    ];
}
