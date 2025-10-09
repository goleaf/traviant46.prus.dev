<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

class Village extends Model
{
    protected $table = 'vdata';

    protected $primaryKey = 'kid';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'kid',
        'owner',
        'name',
        'capital',
        'pop',
        'cp',
        'fieldtype',
        'type',
        'wood',
        'clay',
        'iron',
        'crop',
        'maxstore',
        'maxcrop',
        'woodp',
        'clayp',
        'ironp',
        'cropp',
        'upkeep',
        'created',
        'loyalty',
        'isWW',
        'isFarm',
        'isArtifact',
        'hidden',
        'evasion',
    ];

    protected $casts = [
        'owner' => 'integer',
        'capital' => 'boolean',
        'pop' => 'integer',
        'cp' => 'integer',
        'fieldtype' => 'integer',
        'type' => 'integer',
        'wood' => 'float',
        'clay' => 'float',
        'iron' => 'float',
        'crop' => 'float',
        'maxstore' => 'integer',
        'maxcrop' => 'integer',
        'woodp' => 'integer',
        'clayp' => 'integer',
        'ironp' => 'integer',
        'cropp' => 'integer',
        'upkeep' => 'integer',
        'created' => 'integer',
        'loyalty' => 'float',
        'isWW' => 'boolean',
        'isFarm' => 'boolean',
        'isArtifact' => 'boolean',
        'hidden' => 'boolean',
        'evasion' => 'boolean',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner', 'id');
    }

    public function buildings(): HasOne
    {
        return $this->hasOne(Building::class, 'kid', 'kid');
    }

    public function units(): HasOne
    {
        return $this->hasOne(Unit::class, 'kid', 'kid');
    }

    public function artifacts(): HasMany
    {
        return $this->hasMany(Artifact::class, 'kid', 'kid');
    }

    public function heroes(): HasMany
    {
        return $this->hasMany(Hero::class, 'kid', 'kid');
    }

    public function outgoingAttacks(): HasMany
    {
        return $this->hasMany(Attack::class, 'kid', 'kid');
    }

    public function incomingAttacks(): HasMany
    {
        return $this->hasMany(Attack::class, 'to_kid', 'kid');
    }

    public function scopeCapitals(Builder $query): Builder
    {
        return $query->where('capital', true);
    }

    public function scopeWithPopulationGreaterThan(Builder $query, int $population): Builder
    {
        return $query->where('pop', '>', $population);
    }

    protected function storageCapacity(): Attribute
    {
        return Attribute::make(
            get: fn (): array => [
                'warehouse' => (int) $this->maxstore,
                'granary' => (int) $this->maxcrop,
            ]
        );
    }

    protected function resourceProduction(): Attribute
    {
        return Attribute::make(
            get: fn (): array => [
                'wood' => (int) $this->woodp,
                'clay' => (int) $this->clayp,
                'iron' => (int) $this->ironp,
                'crop' => (int) $this->cropp,
            ]
        );
    }

    protected function createdAt(): Attribute
    {
        return Attribute::make(
            get: fn ($value, array $attributes): ?Carbon => isset($attributes['created']) && (int) $attributes['created'] > 0
                ? Carbon::createFromTimestamp((int) $attributes['created'])
                : null,
            set: fn ($value): array => [
                'created' => $value instanceof Carbon ? $value->getTimestamp() : (int) $value,
            ]
        );
    }
}
