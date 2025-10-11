<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class HeroAdventure extends Model
{
    use HasFactory;

    public const STATUS_AVAILABLE = 'available';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_EXPIRED = 'expired';

    public const DIFFICULTY_EASY = 'easy';
    public const DIFFICULTY_NORMAL = 'normal';
    public const DIFFICULTY_HARD = 'hard';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'hero_id',
        'origin_village_id',
        'target_village_id',
        'difficulty',
        'type',
        'status',
        'available_at',
        'started_at',
        'completed_at',
        'rewards',
        'context',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'origin_village_id' => 'integer',
        'target_village_id' => 'integer',
        'available_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'rewards' => 'array',
        'context' => 'array',
    ];

    public function hero(): BelongsTo
    {
        return $this->belongsTo(Hero::class);
    }

    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $this->scopeStatus($query, self::STATUS_AVAILABLE);
    }

    public function scopeInProgress(Builder $query): Builder
    {
        return $this->scopeStatus($query, self::STATUS_IN_PROGRESS);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $this->scopeStatus($query, self::STATUS_COMPLETED);
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_AVAILABLE => 'Available',
            self::STATUS_IN_PROGRESS => 'In progress',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_EXPIRED => 'Expired',
        ];
    }

    public static function difficultyLabels(): array
    {
        return [
            self::DIFFICULTY_EASY => 'Easy',
            self::DIFFICULTY_NORMAL => 'Normal',
            self::DIFFICULTY_HARD => 'Hard',
        ];
    }

    public function statusLabel(): string
    {
        return self::statusLabels()[$this->status] ?? Str::of($this->status)->headline()->toString();
    }

    public function difficultyLabel(): string
    {
        return self::difficultyLabels()[$this->difficulty] ?? Str::of($this->difficulty)->headline()->toString();
    }

    public function statusPriority(): int
    {
        $order = [
            self::STATUS_AVAILABLE,
            self::STATUS_IN_PROGRESS,
            self::STATUS_COMPLETED,
            self::STATUS_FAILED,
            self::STATUS_EXPIRED,
        ];

        $index = array_search($this->status, $order, true);

        return $index === false ? PHP_INT_MAX : $index;
    }

    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_AVAILABLE;
    }

    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED;
    }

    public function rewardSummary(): array
    {
        $rewards = $this->getAttribute('rewards');

        if (! is_array($rewards) || $rewards === []) {
            return [];
        }

        return Collection::make($rewards)
            ->mapWithKeys(function (mixed $value, string|int $key): array {
                $label = is_string($key) ? Str::of($key)->headline()->toString() : (string) $key;

                return [$label => $this->formatValue($value)];
            })
            ->all();
    }

    public function durationInMinutes(): ?int
    {
        $context = $this->getAttribute('context');

        if (is_array($context) && isset($context['duration_minutes'])) {
            return (int) $context['duration_minutes'];
        }

        if ($this->started_at && $this->completed_at) {
            return $this->started_at->diffInMinutes($this->completed_at);
        }

        return null;
    }

    public function nextImportantMoment(): ?Carbon
    {
        if ($this->isAvailable()) {
            return $this->available_at;
        }

        if ($this->isInProgress()) {
            return $this->completed_at ?? $this->started_at;
        }

        return $this->completed_at;
    }

    protected function formatValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_array($value)) {
            return Collection::make($value)
                ->map(function (mixed $item, string|int $key): string {
                    if (is_string($key)) {
                        return Str::of($key)->headline() . ': ' . $item;
                    }

                    return (string) $item;
                })
                ->implode(', ');
        }

        return (string) $value;
    }
}
