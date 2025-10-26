<?php

declare(strict_types=1);

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $origin
 * @property int $target
 * @property array<string, mixed> $payload
 * @property \Illuminate\Support\Carbon $eta
 */
class Trade extends Model
{
    /** @use HasFactory<\Database\Factories\Game\TradeFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'origin',
        'target',
        'payload',
        'eta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'eta' => 'datetime',
        ];
    }

    public function originVillage(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'origin');
    }

    public function targetVillage(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'target');
    }
}
