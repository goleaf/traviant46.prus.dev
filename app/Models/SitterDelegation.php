<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class SitterDelegation extends Model
{
    use HasFactory;
    use Prunable;

    protected $fillable = [
        'owner_user_id',
        'sitter_user_id',
        'permissions',
        'expires_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'permissions' => 'array',
        'expires_at' => 'datetime',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function sitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sitter_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeForAccount(Builder $query, User|int $user): Builder
    {
        $userId = $user instanceof User ? $user->getKey() : $user;

        return $query->where('owner_user_id', $userId);
    }

    public function scopeForSitter(Builder $query, User|int $sitter): Builder
    {
        $sitterId = $sitter instanceof User ? $sitter->getKey() : $sitter;

        return $query->where('sitter_user_id', $sitterId);
    }

    public function scopeActive(Builder $query, DateTimeInterface|string|null $at = null): Builder
    {
        $moment = $at === null ? Carbon::now() : Carbon::parse($at);

        return $query->where(function (Builder $builder) use ($moment) {
            $builder
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>', $moment);
        });
    }

    public function prunable(): Builder
    {
        return static::query()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', Carbon::now());
    }
}
