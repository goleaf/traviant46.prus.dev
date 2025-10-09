<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Artifact extends Model
{
    protected $table = 'artefacts';

    public $timestamps = false;

    protected $fillable = [
        'uid',
        'kid',
        'release_kid',
        'type',
        'size',
        'conquered',
        'lastupdate',
        'num',
        'effecttype',
        'effect',
        'aoe',
        'status',
        'active',
    ];

    protected $casts = [
        'uid' => 'integer',
        'kid' => 'integer',
        'release_kid' => 'integer',
        'type' => 'integer',
        'size' => 'integer',
        'conquered' => 'integer',
        'lastupdate' => 'integer',
        'num' => 'integer',
        'effecttype' => 'integer',
        'effect' => 'float',
        'aoe' => 'integer',
        'status' => 'boolean',
        'active' => 'boolean',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uid', 'id');
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'kid', 'kid');
    }

    public function releaseVillage(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'release_kid', 'kid');
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

    protected function isActive(): Attribute
    {
        return Attribute::make(
            get: fn ($value, array $attributes): bool => (bool) ($attributes['active'] ?? false),
            set: fn ($value): array => ['active' => $value ? 1 : 0]
        );
    }
}
