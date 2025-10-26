<?php

declare(strict_types=1);

namespace App\Models\Game;

use App\Enums\Game\AdventureStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Adventure extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'village_id',
        'hero_id',
        'status',
        'reward',
        'queued_at',
        'started_at',
        'completes_at',
        'completed_at',
        'failed_at',
        'failure_reason',
    ];

    protected $casts = [
        'reward' => 'array',
        'queued_at' => 'datetime',
        'started_at' => 'datetime',
        'completes_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
        'status' => AdventureStatus::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }

    public function scopeDue(Builder $query): Builder
    {
        return $query
            ->whereIn('status', [AdventureStatus::Pending, AdventureStatus::Active])
            ->where('completes_at', '<=', now())
            ->whereNull('completed_at');
    }

    public function markCompleted(): void
    {
        $this->status = AdventureStatus::Completed;
        $this->completed_at = now();
    }

    public function markFailed(string $reason): void
    {
        $this->status = AdventureStatus::Failed;
        $this->failed_at = now();
        $this->failure_reason = $reason;
    }
}
