<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Hero extends Model
{
    protected $table = 'hero';

    protected $primaryKey = 'uid';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'uid',
        'kid',
        'exp',
        'health',
        'itemHealth',
        'power',
        'offBonus',
        'defBonus',
        'production',
        'productionType',
        'lastupdate',
        'hide',
    ];

    protected $casts = [
        'uid' => 'integer',
        'kid' => 'integer',
        'exp' => 'integer',
        'health' => 'float',
        'itemHealth' => 'integer',
        'power' => 'integer',
        'offBonus' => 'integer',
        'defBonus' => 'integer',
        'production' => 'integer',
        'productionType' => 'integer',
        'lastupdate' => 'integer',
        'hide' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uid', 'id');
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'kid', 'kid');
    }

    protected function healthPercentage(): Attribute
    {
        return Attribute::make(
            get: fn (): float => round((float) $this->health, 2)
        );
    }

    protected function lastUpdatedAt(): Attribute
    {
        return Attribute::make(
            get: fn ($value, array $attributes): ?Carbon => isset($attributes['lastupdate']) && (int) $attributes['lastupdate'] > 0
                ? Carbon::createFromTimestamp((int) $attributes['lastupdate'])
                : null,
            set: fn ($value): array => [
                'lastupdate' => $value instanceof Carbon ? $value->getTimestamp() : (int) $value,
            ]
        );
    }

    protected function isHidden(): Attribute
    {
        return Attribute::make(
            get: fn ($value, array $attributes): bool => (bool) ($attributes['hide'] ?? false),
            set: fn ($value): array => ['hide' => $value ? 1 : 0]
        );
    }
}
