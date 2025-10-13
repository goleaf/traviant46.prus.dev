<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmithyLevel extends Model
{
    use HasFactory;

    protected $table = 'smithy';

    public $timestamps = false;

    protected $fillable = [
        'kid',
    ];

    protected $casts = [
        'kid' => 'integer',
    ];
}
