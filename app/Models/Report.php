<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Game\Village;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int|null $legacy_report_id
 * @property int|null $user_id
 * @property int|null $origin_village_id
 * @property int|null $target_village_id
 * @property string $report_type
 * @property string|null $category
 * @property array<string, mixed>|null $payload
 * @property array{wood?: int, clay?: int, iron?: int, crop?: int}|null $bounty
 * @property array<string, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $triggered_at
 * @property \Illuminate\Support\Carbon|null $viewed_at
 * @property \Illuminate\Support\Carbon|null $archived_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ReportRecipient> $recipients
 */
class Report extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'legacy_report_id',
        'user_id',
        'alliance_id',
        'origin_village_id',
        'target_village_id',
        'report_type',
        'category',
        'delivery_scope',
        'is_system_generated',
        'is_persistent',
        'loss_percentage',
        'payload',
        'bounty',
        'triggered_at',
        'viewed_at',
        'archived_at',
        'deleted_at',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'bounty' => 'array',
            'metadata' => 'array',
            'triggered_at' => 'datetime',
            'viewed_at' => 'datetime',
            'archived_at' => 'datetime',
            'deleted_at' => 'datetime',
            'is_system_generated' => 'bool',
            'is_persistent' => 'bool',
            'loss_percentage' => 'integer',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function originVillage(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'origin_village_id');
    }

    public function targetVillage(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'target_village_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(ReportRecipient::class);
    }

    public function scopeForRecipient(Builder $query, User|int $recipient): Builder
    {
        $recipientId = $recipient instanceof User ? $recipient->getKey() : $recipient;

        return $query->whereHas('recipients', static function (Builder $recipientQuery) use ($recipientId): void {
            $recipientQuery->where('recipient_id', $recipientId);
        });
    }

    public function scopeTriggeredBetween(Builder $query, DateTimeInterface $from, DateTimeInterface $to): Builder
    {
        return $query->whereBetween('triggered_at', [$from, $to]);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('viewed_at');
    }
}
