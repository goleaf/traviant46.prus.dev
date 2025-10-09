<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Attack extends Model
{
    protected $table = 'movement';

    public $timestamps = false;

    protected $fillable = [
        'kid',
        'to_kid',
        'race',
        'u1',
        'u2',
        'u3',
        'u4',
        'u5',
        'u6',
        'u7',
        'u8',
        'u9',
        'u10',
        'u11',
        'ctar1',
        'ctar2',
        'spyType',
        'redeployHero',
        'mode',
        'attack_type',
        'start_time',
        'end_time',
        'data',
        'markState',
        'proc',
    ];

    protected $casts = [
        'kid' => 'integer',
        'to_kid' => 'integer',
        'race' => 'integer',
        'u1' => 'integer',
        'u2' => 'integer',
        'u3' => 'integer',
        'u4' => 'integer',
        'u5' => 'integer',
        'u6' => 'integer',
        'u7' => 'integer',
        'u8' => 'integer',
        'u9' => 'integer',
        'u10' => 'integer',
        'u11' => 'integer',
        'ctar1' => 'integer',
        'ctar2' => 'integer',
        'spyType' => 'integer',
        'redeployHero' => 'boolean',
        'mode' => 'integer',
        'attack_type' => 'integer',
        'markState' => 'integer',
        'proc' => 'boolean',
    ];

    public function origin(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'kid', 'kid');
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'to_kid', 'kid');
    }

    public function scopeEnRoute(Builder $query): Builder
    {
        return $query->where('proc', 0);
    }

    public function scopeArrivingBefore(Builder $query, Carbon $time): Builder
    {
        return $query->where('end_time', '<=', $time->getTimestamp());
    }

    public function scopeAttacks(Builder $query): Builder
    {
        return $query->where('attack_type', '>', 0);
    }

    protected function startTime(): Attribute
    {
        return Attribute::make(
            get: fn ($value, array $attributes): ?Carbon => isset($attributes['start_time']) && $attributes['start_time']
                ? Carbon::createFromTimestamp((int) $attributes['start_time'])
                : null,
            set: fn ($value): array => [
                'start_time' => $value instanceof Carbon ? $value->getTimestamp() : (int) $value,
            ]
        );
    }

    protected function endTime(): Attribute
    {
        return Attribute::make(
            get: fn ($value, array $attributes): ?Carbon => isset($attributes['end_time']) && $attributes['end_time']
                ? Carbon::createFromTimestamp((int) $attributes['end_time'])
                : null,
            set: fn ($value): array => [
                'end_time' => $value instanceof Carbon ? $value->getTimestamp() : (int) $value,
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

                parse_str($raw, $parsed);

                return array_filter($parsed, static fn ($entry) => $entry !== '' && $entry !== null);
            },
            set: fn ($value): array => [
                'data' => is_array($value)
                    ? json_encode($value, JSON_THROW_ON_ERROR)
                    : (string) $value,
            ]
        );
    }

    protected function isProcessed(): Attribute
    {
        return Attribute::make(
            get: fn ($value, array $attributes): bool => (bool) ($attributes['proc'] ?? false),
            set: fn ($value): array => ['proc' => $value ? 1 : 0]
        );
    }
}
