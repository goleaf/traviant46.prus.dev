<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AllianceForum extends Model
{
    /** @use HasFactory<\Database\Factories\AllianceForumFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'alliance_id',
        'name',
        'description',
        'position',
        'visible_to_sitters',
        'moderators_only',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'visible_to_sitters' => 'boolean',
            'moderators_only' => 'boolean',
        ];
    }

    public function alliance(): BelongsTo
    {
        return $this->belongsTo(Alliance::class);
    }

    public function topics(): HasMany
    {
        return $this->hasMany(AllianceTopic::class)->orderByDesc('is_pinned')->orderByDesc('last_posted_at');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position');
    }
}
