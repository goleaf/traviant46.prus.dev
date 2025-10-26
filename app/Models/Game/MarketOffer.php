<?php

declare(strict_types=1);

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $village_id
 * @property array<string, int> $give
 * @property array<string, int> $want
 * @property int $merchants
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class MarketOffer extends Model
{
    /** @use HasFactory<\Database\Factories\Game\MarketOfferFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'village_id',
        'give',
        'want',
        'merchants',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'give' => 'array',
            'want' => 'array',
            'merchants' => 'integer',
        ];
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }
}
