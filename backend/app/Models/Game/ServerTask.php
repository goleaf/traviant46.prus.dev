<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServerTask extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'type',
        'status',
        'attempts',
        'payload',
        'available_at',
        'started_at',
        'finished_at',
        'last_error',
    ];

    protected $casts = [
        'payload' => 'array',
        'available_at' => 'datetime',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function scopeDue(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_PENDING)
            ->where(function (Builder $builder) {
                $builder
                    ->whereNull('available_at')
                    ->orWhere('available_at', '<=', now());
            });
    }

    public function markProcessing(): void
    {
        $this->status = self::STATUS_PROCESSING;
        $this->started_at = now();
        $this->attempts++;
    }

    public function markCompleted(): void
    {
        $this->status = self::STATUS_COMPLETED;
        $this->finished_at = now();
        $this->last_error = null;
    }

    public function markFailed(string $message): void
    {
        $this->status = self::STATUS_FAILED;
        $this->finished_at = now();
        $this->last_error = $message;
    }
}
