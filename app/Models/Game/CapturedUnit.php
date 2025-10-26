<?php

declare(strict_types=1);

namespace App\Models\Game;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int|null $legacy_trapped_id
 * @property int $captor_village_id
 * @property int|null $source_village_id
 * @property int|null $owner_user_id
 * @property array<string, int>|null $unit_composition
 * @property array<string, mixed>|null $metadata
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $captured_at
 * @property \Illuminate\Support\Carbon|null $released_at
 * @property \Illuminate\Support\Carbon|null $executed_at
 */
class CapturedUnit extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'legacy_trapped_id',
        'captor_village_id',
        'source_village_id',
        'owner_user_id',
        'unit_composition',
        'status',
        'captured_at',
        'released_at',
        'executed_at',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit_composition' => 'array',
            'metadata' => 'array',
            'captured_at' => 'datetime',
            'released_at' => 'datetime',
            'executed_at' => 'datetime',
        ];
    }

    public function captor(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'captor_village_id');
    }

    public function sourceVillage(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'source_village_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }
}
