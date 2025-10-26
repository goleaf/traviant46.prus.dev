<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class SitterAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'sitter_id',
        'permissions',
        'expires_at',
    ];

    protected $casts = [
        'permissions' => 'array',
        'expires_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(User::class, 'account_id');
    }

    public function sitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sitter_id');
    }

    public function scopeForAccount(Builder $query, User|int $user): Builder
    {
        $userId = $user instanceof User ? $user->getKey() : $user;

        return $query->where('account_id', $userId);
    }

    public function scopeForSitter(Builder $query, User|int $sitter): Builder
    {
        $sitterId = $sitter instanceof User ? $sitter->getKey() : $sitter;

        return $query->where('sitter_id', $sitterId);
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
}
