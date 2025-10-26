<?php

declare(strict_types=1);

namespace App\Models\Game;

use App\Enums\Game\ServerTaskStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServerTask extends Model
{
    use HasFactory;

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
        'status' => ServerTaskStatus::class,
    ];

    public function scopeDue(Builder $query): Builder
    {
        return $query
            ->where('status', ServerTaskStatus::Pending)
            ->where(function (Builder $builder) {
                $builder
                    ->whereNull('available_at')
                    ->orWhere('available_at', '<=', now());
            });
    }

    public function markProcessing(): void
    {
        $this->status = ServerTaskStatus::Processing;
        $this->started_at = now();
        $this->attempts++;
    }

    public function markCompleted(): void
    {
        $this->status = ServerTaskStatus::Completed;
        $this->finished_at = now();
        $this->last_error = null;
    }

    public function markFailed(string $message): void
    {
        $this->status = ServerTaskStatus::Failed;
        $this->finished_at = now();
        $this->last_error = $message;
    }
}
