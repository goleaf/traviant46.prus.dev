<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Report extends Model
{
    protected $table = 'ndata';

    public $timestamps = false;

    protected $fillable = [
        'aid',
        'uid',
        'isEnforcement',
        'kid',
        'to_kid',
        'type',
        'bounty',
        'data',
        'time',
        'private_key',
        'viewed',
        'archive',
        'deleted',
        'losses',
        'non_deletable',
    ];

    protected $casts = [
        'aid' => 'integer',
        'uid' => 'integer',
        'isEnforcement' => 'boolean',
        'kid' => 'integer',
        'to_kid' => 'integer',
        'type' => 'integer',
        'time' => 'integer',
        'viewed' => 'boolean',
        'archive' => 'boolean',
        'deleted' => 'boolean',
        'losses' => 'integer',
        'non_deletable' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uid', 'id');
    }

    public function alliance(): BelongsTo
    {
        return $this->belongsTo(Alliance::class, 'aid', 'id');
    }

    public function origin(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'kid', 'kid');
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'to_kid', 'kid');
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('viewed', false)->where('deleted', false);
    }

    public function scopeOfType(Builder $query, int $type): Builder
    {
        return $query->where('type', $type);
    }

    protected function bountyResources(): Attribute
    {
        return Attribute::make(
            get: function ($value, array $attributes): array {
                $raw = $attributes['bounty'] ?? '';
                if ($raw === '') {
                    return [];
                }

                $parts = array_map('trim', explode(',', $raw));

                return array_map('intval', $parts);
            },
            set: fn ($value): array => [
                'bounty' => implode(',', array_map('intval', (array) $value)),
            ]
        );
    }

    protected function payload(): Attribute
    {
        return Attribute::make(
            get: function ($value, array $attributes): array {
                $raw = $attributes['data'] ?? null;
                if ($raw === null || $raw === '') {
                    return [];
                }

                $decoded = json_decode($raw, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return $decoded;
                }

                return ['raw' => $raw];
            },
            set: fn ($value): array => [
                'data' => json_encode($value, JSON_THROW_ON_ERROR),
            ]
        );
    }

    protected function happenedAt(): Attribute
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
}
