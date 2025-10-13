<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Hero extends Model
{
    use HasFactory;

    protected $table = 'hero';

    public $timestamps = false;

    protected $fillable = [
        'uid',
        'kid',
        'lastupdate',
        'health',
        'experience',
        'level',
        'points',
        'speed',
    ];

    protected $casts = [
        'uid' => 'integer',
        'kid' => 'integer',
        'lastupdate' => 'integer',
        'health' => 'integer',
        'experience' => 'integer',
        'level' => 'integer',
        'points' => 'integer',
        'speed' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserAccount::class, 'uid');
    }
}
