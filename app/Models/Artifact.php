<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Artifact extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'type',
        'effect_scope',
        'effects',
        'metadata',
        'owner_alliance_id',
        'owner_user_id',
        'spawn_village_id',
        'captured_at',
        'cooldown_ends_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'effects' => 'array',
        'metadata' => 'array',
        'captured_at' => 'datetime',
        'cooldown_ends_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Artifact $artifact): void {
            $artifact->slug ??= Str::slug($artifact->name);
        });
    }

    public function alliances(): BelongsToMany
    {
        return $this->belongsToMany(Alliance::class, 'alliance_artifact')
            ->withPivot(['captured_at'])
            ->withTimestamps();
    }

    public function holders(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'artifact_user')
            ->withPivot(['assigned_at'])
            ->withTimestamps();
    }

    public function artifactLogs(): HasMany
    {
        return $this->hasMany(ArtifactLog::class)->latest('captured_at');
    }

    public function ownerAlliance(): BelongsTo
    {
        return $this->belongsTo(Alliance::class, 'owner_alliance_id');
    }

    public function ownerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function spawnVillage(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'spawn_village_id');
    }

    protected function displayName(): Attribute
    {
        return Attribute::get(fn (): string => sprintf('%s (%s)', $this->name, $this->type));
    }

    protected function isGlobal(): Attribute
    {
        return Attribute::get(fn (): bool => $this->effect_scope === 'global');
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->whereNull('owner_user_id')->whereNull('owner_alliance_id');
    }
}
