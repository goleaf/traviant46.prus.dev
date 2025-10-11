<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HeroAccountEntry extends Model
{
    use HasFactory;

    protected $table = 'accounting';

    public $timestamps = false;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'uid',
        'cause',
        'reserve',
        'balance',
        'time',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'uid' => 'integer',
        'reserve' => 'integer',
        'balance' => 'integer',
        'time' => 'integer',
    ];

    public function hero(): BelongsTo
    {
        return $this->belongsTo(Hero::class, 'uid', 'uid');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uid');
    }
}
