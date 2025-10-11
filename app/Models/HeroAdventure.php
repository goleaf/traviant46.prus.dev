<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HeroAdventure extends Model
{
    use HasFactory;

    protected $table = 'adventure';

    public $timestamps = false;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'uid',
        'kid',
        'dif',
        'time',
        'end',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'uid' => 'integer',
        'kid' => 'integer',
        'dif' => 'integer',
        'time' => 'integer',
        'end' => 'boolean',
    ];

    public function hero(): BelongsTo
    {
        return $this->belongsTo(Hero::class, 'uid', 'uid');
    }
}
