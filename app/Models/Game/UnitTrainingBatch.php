<?php

declare(strict_types=1);

namespace App\Models\Game;

use App\Enums\Game\UnitTrainingBatchStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnitTrainingBatch extends Model
{
    use HasFactory;

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
        'status' => UnitTrainingBatchStatus::class,
    ];

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }

    public function scopeDue(Builder $query): Builder
    {
        return $query
            ->where('status', UnitTrainingBatchStatus::Pending)
            ->whereNull('processed_at')
            ->where('completes_at', '<=', now());
    }

    public function markProcessing(): void
    {
        $this->status = UnitTrainingBatchStatus::Processing;
        $this->starts_at = $this->starts_at ?? now();
    }

    public function markCompleted(): void
    {
        $this->status = UnitTrainingBatchStatus::Completed;
        $this->processed_at = now();
    }

    public function markFailed(string $reason): void
    {
        $this->status = UnitTrainingBatchStatus::Failed;
        $this->failed_at = now();
        $this->failure_reason = $reason;
    }
}
