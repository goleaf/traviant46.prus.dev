<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AllianceRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AllianceMember extends Model
{
    /** @use HasFactory<\Database\Factories\AllianceMemberFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'alliance_id',
        'user_id',
        'role',
        'joined_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
            'role' => AllianceRole::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(static function (self $membership): void {
            if ($membership->joined_at === null) {
                $membership->joined_at = now();
            }
        });

        static::saved(static fn (self $membership) => $membership->syncUserAlliance());
        static::deleted(static fn (self $membership) => $membership->clearUserAlliance());
    }

    public function alliance(): BelongsTo
    {
        return $this->belongsTo(Alliance::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function badgeColor(): string
    {
        $role = $this->role instanceof AllianceRole ? $this->role : AllianceRole::Member;

        return $role->badgeColor();
    }

    public function badgeLabel(): string
    {
        $role = $this->role instanceof AllianceRole ? $this->role : AllianceRole::Member;

        return $role->label();
    }

    public function canManageProfile(): bool
    {
        return $this->role instanceof AllianceRole && $this->role->canManageProfile();
    }

    public function canManageMembers(): bool
    {
        return $this->role instanceof AllianceRole && $this->role->canManageMembers();
    }

    public function canManageDiplomacy(): bool
    {
        return $this->role instanceof AllianceRole && $this->role->canManageDiplomacy();
    }

    public function canModerateForums(): bool
    {
        return $this->role instanceof AllianceRole && $this->role->canModerateForums();
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->getKey());
    }

    private function syncUserAlliance(): void
    {
        $user = $this->user;

        if (! $user instanceof User) {
            return;
        }

        if ((int) $user->current_alliance_id !== (int) $this->alliance_id) {
            $user->forceFill(['current_alliance_id' => $this->alliance_id])->saveQuietly();
        }
    }

    private function clearUserAlliance(): void
    {
        $user = $this->user;

        if (! $user instanceof User) {
            return;
        }

        if ((int) $user->current_alliance_id === (int) $this->alliance_id) {
            $user->forceFill(['current_alliance_id' => null])->saveQuietly();
        }
    }
}
