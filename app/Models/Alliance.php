<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Alliance extends Model
{
    /** @use HasFactory<\Database\Factories\AllianceFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'tag',
        'description',
        'message_of_day',
        'founder_id',
    ];

    public function founder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'founder_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(AllianceMember::class)->with('user');
    }

    public function forums(): HasMany
    {
        return $this->hasMany(AllianceForum::class)->orderBy('position');
    }

    public function topics(): HasManyThrough
    {
        return $this->hasManyThrough(AllianceTopic::class, AllianceForum::class, 'alliance_id', 'forum_id');
    }

    public function posts(): HasManyThrough
    {
        return $this->hasManyThrough(AlliancePost::class, AllianceTopic::class, 'alliance_id', 'topic_id');
    }

    public function diplomacyOffers(): HasMany
    {
        return $this->hasMany(AllianceDiplomacy::class, 'alliance_id');
    }

    public function incomingDiplomacyOffers(): HasMany
    {
        return $this->hasMany(AllianceDiplomacy::class, 'target_alliance_id');
    }

    public function membershipFor(User $user): ?AllianceMember
    {
        $relation = $this->relationLoaded('members') ? $this->members : $this->members()->get();

        return $relation->firstWhere('user_id', $user->getKey());
    }

    public function scopeTagged(Builder $query, string $tag): Builder
    {
        return $query->whereRaw('LOWER(tag) = ?', [mb_strtolower($tag)]);
    }
}
