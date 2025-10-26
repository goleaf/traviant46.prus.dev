<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $report_id
 * @property int $recipient_id
 * @property string $visibility_scope
 * @property string $status
 * @property bool $is_flagged
 * @property array<string, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $viewed_at
 * @property \Illuminate\Support\Carbon|null $archived_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $forwarded_at
 */
class ReportRecipient extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'report_id',
        'recipient_id',
        'recipient_alliance_id',
        'visibility_scope',
        'status',
        'is_flagged',
        'viewed_at',
        'archived_at',
        'deleted_at',
        'forwarded_at',
        'share_token',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'viewed_at' => 'datetime',
            'archived_at' => 'datetime',
            'deleted_at' => 'datetime',
            'forwarded_at' => 'datetime',
            'is_flagged' => 'bool',
            'metadata' => 'array',
        ];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('status', 'unread')->whereNull('viewed_at');
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->whereNull('deleted_at');
    }
}
