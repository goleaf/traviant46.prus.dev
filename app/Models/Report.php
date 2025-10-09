<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'alliance_id',
        'attack_id',
        'type',
        'data',
        'was_victory',
        'is_read',
        'read_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'data' => 'array',
        'was_victory' => 'boolean',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Report $report): void {
            $report->type = strtolower($report->type);
            $report->is_read ??= false;
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function alliance(): BelongsTo
    {
        return $this->belongsTo(Alliance::class);
    }

    public function attack(): BelongsTo
    {
        return $this->belongsTo(Attack::class);
    }

    protected function summary(): Attribute
    {
        return Attribute::get(function (): string {
            $data = $this->data ?? [];
            $attacker = $data['attacker'] ?? 'Unknown';
            $defender = $data['defender'] ?? 'Unknown';

            return sprintf('%s vs %s - %s', $attacker, $defender, ucfirst($this->type));
        });
    }

    protected function isUnread(): Attribute
    {
        return Attribute::get(fn (): bool => !$this->is_read);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('is_read', false);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForAlliance(Builder $query, int $allianceId): Builder
    {
        return $query->where('alliance_id', $allianceId);
    }
}
