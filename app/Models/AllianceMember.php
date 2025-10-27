<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AllianceRole as AllianceRoleEnum;
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
        'alliance_role_id',
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
            'role' => AllianceRoleEnum::class,
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

    /**
     * Resolve the configurable alliance role assigned to the member, if any.
     */
    public function allianceRole(): BelongsTo
    {
        return $this->belongsTo(\App\Models\AllianceRole::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function badgeColor(): string
    {
        $role = $this->role instanceof AllianceRoleEnum ? $this->role : AllianceRoleEnum::Member;

        return $role->badgeColor();
    }

    public function badgeLabel(): string
    {
        $role = $this->role instanceof AllianceRoleEnum ? $this->role : AllianceRoleEnum::Member;

        return $role->label();
    }

    public function canManageProfile(): bool
    {
        if ($this->hasPermission('manage_profile')) {
            return true;
        }

        return $this->role instanceof AllianceRoleEnum && $this->role->canManageProfile();
    }

    public function canManageMembers(): bool
    {
        if ($this->hasPermission('manage_members')) {
            return true;
        }

        return $this->role instanceof AllianceRoleEnum && $this->role->canManageMembers();
    }

    public function canManageDiplomacy(): bool
    {
        if ($this->hasPermission('manage_diplomacy')) {
            return true;
        }

        return $this->role instanceof AllianceRoleEnum && $this->role->canManageDiplomacy();
    }

    public function canModerateForums(): bool
    {
        if ($this->hasPermission('moderate_forums')) {
            return true;
        }

        return $this->role instanceof AllianceRoleEnum && $this->role->canModerateForums();
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

    /**
     * Determine whether the attached configurable role grants a permission key.
     */
    private function hasPermission(string $permission): bool
    {
        $role = $this->allianceRole;

        if (! $role instanceof \App\Models\AllianceRole) {
            return false;
        }

        $permissions = $role->permissions;

        if (! is_array($permissions)) {
            return false;
        }

        return in_array($permission, $permissions, true);
    }
}
