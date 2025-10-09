<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Message extends Model
{
    protected $table = 'mdata';

    public $timestamps = false;

    protected $fillable = [
        'uid',
        'to_uid',
        'topic',
        'message',
        'viewed',
        'archived',
        'delete_receiver',
        'delete_sender',
        'reported',
        'md5_checksum',
        'mode',
        'time',
        'autoType',
        'isAlliance',
    ];

    protected $casts = [
        'uid' => 'integer',
        'to_uid' => 'integer',
        'viewed' => 'boolean',
        'archived' => 'boolean',
        'delete_receiver' => 'boolean',
        'delete_sender' => 'boolean',
        'reported' => 'boolean',
        'mode' => 'integer',
        'time' => 'integer',
        'autoType' => 'integer',
        'isAlliance' => 'boolean',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uid', 'id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_uid', 'id');
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('viewed', false)->where('delete_receiver', false);
    }

    public function scopeFromUser(Builder $query, int $userId): Builder
    {
        return $query->where('uid', $userId);
    }

    protected function sentAt(): Attribute
    {
        return Attribute::make(
            get: fn ($value, array $attributes): ?Carbon => isset($attributes['time']) && (int) $attributes['time'] > 0
                ? Carbon::createFromTimestamp((int) $attributes['time'])
                : null,
            set: fn ($value): array => [
                'time' => $value instanceof Carbon ? $value->getTimestamp() : (int) $value,
            ]
        );
    }

    protected function summary(): Attribute
    {
        return Attribute::make(
            get: fn (): string => trim(mb_strimwidth(strip_tags((string) $this->message), 0, 120, '…'))
        );
    }
}
