<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Alliance extends Model
{
    protected $table = 'alidata';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'tag',
        'desc1',
        'desc2',
        'info1',
        'info2',
        'forumLink',
        'max',
        'total_attack_points',
        'total_defense_points',
        'week_attack_points',
        'week_defense_points',
        'week_robber_points',
        'week_pop_changes',
        'training_bonus_level',
        'training_bonus_contributions',
        'armor_bonus_level',
        'armor_bonus_contributions',
        'cp_bonus_level',
        'cp_bonus_contributions',
        'trade_bonus_level',
        'trade_bonus_contributions',
    ];

    protected $casts = [
        'max' => 'integer',
        'total_attack_points' => 'integer',
        'total_defense_points' => 'integer',
        'week_attack_points' => 'integer',
        'week_defense_points' => 'integer',
        'week_robber_points' => 'integer',
        'week_pop_changes' => 'integer',
        'training_bonus_level' => 'integer',
        'training_bonus_contributions' => 'integer',
        'armor_bonus_level' => 'integer',
        'armor_bonus_contributions' => 'integer',
        'cp_bonus_level' => 'integer',
        'cp_bonus_contributions' => 'integer',
        'trade_bonus_level' => 'integer',
        'trade_bonus_contributions' => 'integer',
    ];

    public function members(): HasMany
    {
        return $this->hasMany(User::class, 'aid', 'id');
    }

    public function villages(): HasManyThrough
    {
        return $this->hasManyThrough(
            Village::class,
            User::class,
            'aid',
            'owner',
            'id',
            'id'
        );
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'aid', 'id');
    }

    public function messages(): HasManyThrough
    {
        return $this->hasManyThrough(
            Message::class,
            User::class,
            'aid',
            'uid',
            'id',
            'id'
        )->where('isAlliance', true);
    }

    public function allies(): BelongsToMany
    {
        return $this->belongsToMany(
            Alliance::class,
            'diplomacy',
            'aid1',
            'aid2',
            'id',
            'id'
        )->withPivot(['type', 'accepted']);
    }

    public function scopeWithTag(Builder $query, string $tag): Builder
    {
        return $query->where('tag', $tag);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function (Builder $builder) use ($term) {
            $builder
                ->where('name', 'like', "%{$term}%")
                ->orWhere('tag', 'like', "%{$term}%");
        });
    }

    protected function displayName(): Attribute
    {
        return Attribute::make(
            get: fn (): string => sprintf('%s [%s]', $this->name, $this->tag)
        );
    }

    protected function bonusLevels(): Attribute
    {
        return Attribute::make(
            get: fn (): array => [
                'training' => (int) $this->training_bonus_level,
                'armor' => (int) $this->armor_bonus_level,
                'culture_points' => (int) $this->cp_bonus_level,
                'trade' => (int) $this->trade_bonus_level,
            ]
        );
    }
}
