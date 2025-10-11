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

class Alliance extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'tag',
        'description',
        'motd',
        'diplomacy',
        'settings',
        'owner_id',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'diplomacy' => 'array',
        'settings' => 'array',
        'statistics' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (Alliance $alliance): void {
            $alliance->tag = strtoupper(trim($alliance->tag));
        });
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'alliance_user')
            ->withPivot([
                'role',
                'permissions',
                'contribution_stats',
                'joined_at',
            ])
            ->withTimestamps();
    }

    public function villages(): HasMany
    {
        return $this->hasMany(Village::class);
    }

    public function artifacts(): BelongsToMany
    {
        return $this->belongsToMany(Artifact::class)
            ->withPivot(['captured_at'])
            ->withTimestamps();
    }

    public function diplomacy(): HasMany
    {
        $relation = $this->hasMany(AllianceDiplomacy::class, 'aid1');

        $query = $relation->getQuery();
        $query->wheres = [];
        if (method_exists($query, 'setBindings')) {
            $query->setBindings([], 'where');
        }

        return $relation->where(function (Builder $builder): void {
            $builder
                ->where('aid1', $this->getKey())
                ->orWhere('aid2', $this->getKey());
        });
    }

    public function forum(): HasMany
    {
        return $this->hasMany(AllianceForum::class, 'aid');
    }

    public function bonuses(): HasMany
    {
        return $this->hasMany(AllianceBonus::class, 'aid');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    protected function displayName(): Attribute
    {
        return Attribute::get(fn (): string => sprintf('%s [%s]', $this->name, $this->tag));
    }

    protected function tag(): Attribute
    {
        return Attribute::set(fn (string $value): string => strtoupper($value));
    }

    public function scopeWithTag(Builder $query, string $tag): Builder
    {
        return $query->where('tag', strtoupper($tag));
    }

    public function scopeTop(Builder $query, int $limit = 10): Builder
    {
        return $query->orderByDesc('victory_points')->limit($limit);
    }

    protected function totalPopulation(): Attribute
    {
        return Attribute::get(function (): int {
            if ($this->relationLoaded('villages')) {
                return (int) $this->villages->sum('population');
            }

            $statistics = $this->statistics;
            if (is_array($statistics) && array_key_exists('total_population', $statistics)) {
                return (int) $statistics['total_population'];
            }

            return (int) $this->villages()->sum('population');
        });
    }

    protected function totalVillages(): Attribute
    {
        return Attribute::get(function (): int {
            if ($this->relationLoaded('villages')) {
                return (int) $this->villages->count();
            }

            $statistics = $this->statistics;
            if (is_array($statistics) && array_key_exists('total_villages', $statistics)) {
                return (int) $statistics['total_villages'];
            }

            return (int) $this->villages()->count();
        });
    }

    protected function rank(): Attribute
    {
        return Attribute::get(function (): ?int {
            $statistics = $this->statistics;
            if (is_array($statistics) && array_key_exists('rank', $statistics)) {
                return (int) $statistics['rank'];
            }

            if (!$this->exists) {
                return null;
            }

            $victoryPoints = $this->getAttribute('victory_points');
            if ($victoryPoints === null) {
                return null;
            }

            return (int) (static::query()
                ->where('victory_points', '>', $victoryPoints)
                ->count() + 1);
        });
    }
}
