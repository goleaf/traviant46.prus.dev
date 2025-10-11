<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnitMovement extends Model
{
    use HasFactory;

    public const STATUS_QUEUED = 'queued';
    public const STATUS_TRAVELLING = 'travelling';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    public const MISSION_ATTACK = 'attack';
    public const MISSION_RAID = 'raid';
    public const MISSION_SPY = 'spy';
    public const MISSION_ADVENTURE = 'adventure';
    public const MISSION_REINFORCEMENT = 'reinforcement';
    public const MISSION_RETURN = 'return';
    public const MISSION_SETTLERS = 'settlers';
    public const MISSION_EVASION = 'evasion';

    protected $fillable = [
        'origin_village_id',
        'target_village_id',
        'mission',
        'status',
        'payload',
        'metadata',
        'departed_at',
        'arrives_at',
        'processing_started_at',
        'processed_at',
        'failed_at',
        'failure_reason',
    ];

    protected $casts = [
        'payload' => 'array',
        'metadata' => 'array',
        'departed_at' => 'datetime',
        'arrives_at' => 'datetime',
        'processing_started_at' => 'datetime',
        'processed_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function origin(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'origin_village_id');
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'target_village_id');
    }

    public function scopeDue(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_TRAVELLING)
            ->whereNotNull('arrives_at')
            ->where('arrives_at', '<=', now());
    }

    public function scopeMissionIn(Builder $query, array $missions): Builder
    {
        return $query->whereIn('mission', $missions);
    }

    public function isDueForProcessing(): bool
    {
        return $this->status === self::STATUS_TRAVELLING
            && $this->arrives_at !== null
            && $this->arrives_at->isPast();
    }

    public function markProcessing(): void
    {
        $this->status = self::STATUS_PROCESSING;
        $this->processing_started_at = now();
    }

    public function markCompleted(array $metadata = []): void
    {
        if (!empty($metadata)) {
            $this->mergeMetadata($metadata);
        }

        $this->status = self::STATUS_COMPLETED;
        $this->processed_at = now();
        $this->failure_reason = null;
    }

    public function markFailed(string $message): void
    {
        $this->status = self::STATUS_FAILED;
        $this->failed_at = now();
        $this->failure_reason = $message;
    }

    public function mergeMetadata(array $metadata): void
    {
        $current = $this->metadata ?? [];
        $this->metadata = array_replace_recursive($current, $metadata);
    }

    /**
     * @return array<int, array{unit_type_id:int, quantity:int}>
     */
    public function units(): array
    {
        $units = data_get($this->payload, 'units', []);

        if (!is_array($units)) {
            return [];
        }

        return array_values(array_filter(array_map(static function ($unit): ?array {
            $unitTypeId = (int) data_get($unit, 'unit_type_id');
            $quantity = (int) data_get($unit, 'quantity');

            if ($unitTypeId <= 0 || $quantity <= 0) {
                return null;
            }

            return [
                'unit_type_id' => $unitTypeId,
                'quantity' => $quantity,
            ];
        }, $units)));
    }
}

