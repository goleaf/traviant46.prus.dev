<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AllianceDiplomacyStatus;
use App\Enums\AllianceDiplomacyType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AllianceDiplomacy extends Model
{
    /** @use HasFactory<\Database\Factories\AllianceDiplomacyFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'alliance_id',
        'target_alliance_id',
        'type',
        'status',
        'note',
        'initiated_by',
        'responded_by',
        'responded_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => AllianceDiplomacyType::class,
            'status' => AllianceDiplomacyStatus::class,
            'responded_at' => 'datetime',
        ];
    }

    public function alliance(): BelongsTo
    {
        return $this->belongsTo(Alliance::class);
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(Alliance::class, 'target_alliance_id');
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function responder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responded_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', AllianceDiplomacyStatus::Pending->value);
    }

    public function markAccepted(User $responder): void
    {
        $this->forceFill([
            'status' => AllianceDiplomacyStatus::Accepted,
            'responded_by' => $responder->getKey(),
            'responded_at' => now(),
        ])->save();
    }

    public function markRejected(User $responder): void
    {
        $this->forceFill([
            'status' => AllianceDiplomacyStatus::Rejected,
            'responded_by' => $responder->getKey(),
            'responded_at' => now(),
        ])->save();
    }

    public function cancel(User $initiator): void
    {
        $this->forceFill([
            'status' => AllianceDiplomacyStatus::Cancelled,
            'responded_by' => $initiator->getKey(),
            'responded_at' => now(),
        ])->save();
    }
}
