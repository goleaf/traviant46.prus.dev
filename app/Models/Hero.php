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
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use RuntimeException;

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
        'energy',
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
        'energy' => 'integer',
        'attributes' => 'array',
        'equipment' => 'array',
        'is_active' => 'boolean',
        'last_moved_at' => 'datetime',
    ];

    public const ATTR_POINTS_PER_LEVEL = 4;

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

    public function gainExperience(int $amount): self
    {
        if ($amount <= 0) {
            return $this;
        }

        $this->experience += $amount;

        while ($this->experience >= $this->experienceForLevel($this->level + 1)) {
            $this->levelUp(false);
        }

        $this->save();

        return $this;
    }

    public function levelUp(bool $save = true): self
    {
        $this->level++;

        $attributes = $this->attributes ?? [];
        $attributes['unspent_points'] = (int) ($attributes['unspent_points'] ?? 0) + self::ATTR_POINTS_PER_LEVEL;
        $this->attributes = $attributes;

        if ($save) {
            $this->save();
        }

        return $this;
    }

    public function equipItem(HeroItem $item): self
    {
        if ($item->hero_id !== $this->id) {
            throw new InvalidArgumentException('Item does not belong to this hero.');
        }

        $slot = $item->slot;

        $this->items()
            ->where('slot', $slot)
            ->where('is_equipped', true)
            ->get()
            ->each(function (HeroItem $equipped) use ($item): void {
                if ($equipped->id === $item->id) {
                    return;
                }

                $equipped->forceFill(['is_equipped' => false])->save();
            });

        $item->forceFill(['is_equipped' => true])->save();

        $equipment = $this->equipment ?? [];
        $equipment[$slot] = $item->id;
        $this->equipment = $equipment;

        $this->save();

        return $this;
    }

    public function startAdventure(HeroAdventure $adventure): HeroAdventure
    {
        if ($adventure->hero_id !== $this->id) {
            throw new InvalidArgumentException('Adventure does not belong to this hero.');
        }

        if ($this->status === 'dead') {
            throw new RuntimeException('A dead hero cannot start an adventure.');
        }

        if ($this->status === 'adventuring') {
            throw new RuntimeException('Hero is already on an adventure.');
        }

        if ($adventure->status !== 'available') {
            throw new RuntimeException('Adventure is not available.');
        }

        $energyCost = $this->energyCostForAdventure($adventure->difficulty);

        if ($this->energy < $energyCost) {
            throw new RuntimeException('Not enough energy to start the adventure.');
        }

        $now = Carbon::now();

        $this->forceFill([
            'energy' => $this->energy - $energyCost,
            'status' => 'adventuring',
            'last_moved_at' => $now,
        ])->save();

        $adventure->forceFill([
            'status' => 'in_progress',
            'started_at' => $now,
        ])->save();

        return $adventure->refresh();
    }

    protected function experienceForLevel(int $level): int
    {
        if ($level <= 1) {
            return 0;
        }

        return 25 * $level * ($level + 1);
    }

    protected function energyCostForAdventure(string $difficulty): int
    {
        return match ($difficulty) {
            'easy' => 10,
            'hard' => 30,
            default => 20,
        };
    }
}
