<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnitTrainingBatch extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'village_id',
        'unit_type_id',
        'quantity',
        'queue_position',
        'training_building',
        'status',
        'metadata',
        'queued_at',
        'starts_at',
        'completes_at',
        'processed_at',
        'failed_at',
        'failure_reason',
    ];

    protected $casts = [
        'metadata' => 'array',
        'queued_at' => 'datetime',
        'starts_at' => 'datetime',
        'completes_at' => 'datetime',
        'processed_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }

    public function unitType(): BelongsTo
    {
        return $this->belongsTo(UnitType::class);
    }

    public function scopeDue(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_PENDING)
            ->whereNull('processed_at')
            ->where('completes_at', '<=', now());
    }

    public function markProcessing(): void
    {
        $this->status = self::STATUS_PROCESSING;
        $this->starts_at = $this->starts_at ?? now();
    }

    public function markCompleted(): void
    {
        $this->status = self::STATUS_COMPLETED;
        $this->processed_at = now();
    }

    public function markFailed(string $reason): void
    {
        $this->status = self::STATUS_FAILED;
        $this->failed_at = now();
        $this->failure_reason = $reason;
    }
}
