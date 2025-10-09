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

    public function members(): HasMany
    {
        return $this->hasMany(User::class);
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
}
