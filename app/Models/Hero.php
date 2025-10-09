<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Hero extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'village_id',
        'home_village_id',
        'name',
        'level',
        'experience',
        'health',
        'status',
        'attributes',
        'equipment',
        'is_active',
        'last_moved_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'level' => 'integer',
        'experience' => 'integer',
        'health' => 'integer',
        'attributes' => 'array',
        'equipment' => 'array',
        'is_active' => 'boolean',
        'last_moved_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (Hero $hero): void {
            $hero->name = trim($hero->name);
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }

    public function homeVillage(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'home_village_id');
    }

    public function face(): HasOne
    {
        return $this->hasOne(HeroFace::class);
    }

    public function inventory(): HasOne
    {
        return $this->hasOne(HeroInventory::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(HeroItem::class);
    }

    public function adventures(): HasMany
    {
        return $this->hasMany(HeroAdventure::class);
    }

    public function accountEntries(): HasMany
    {
        return $this->hasMany(HeroAccountEntry::class);
    }

    protected function isAlive(): Attribute
    {
        return Attribute::get(fn (): bool => $this->health > 0);
    }

    protected function power(): Attribute
    {
        return Attribute::get(function (): int {
            $attributes = $this->attributes ?? [];
            $base = (int) ($attributes['strength'] ?? 0);
            $bonus = (int) ($attributes['offence_bonus'] ?? 0);

            return $base + $bonus + ($this->level * 5);
        });
    }

    public function scopeAlive(Builder $query): Builder
    {
        return $query->where('health', '>', 0);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where(function (Builder $builder): void {
            $builder
                ->where('status', 'idle')
                ->orWhere('status', 'defending');
        });
    }

    public function scopeByName(Builder $query, string $name): Builder
    {
        return $query->whereRaw('LOWER(name) = ?', [strtolower($name)]);
    }
}
