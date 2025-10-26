<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SitterPermission;
use App\Enums\SitterPermissionPreset;
use App\ValueObjects\SitterPermissionSet;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
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
        'expires_at' => 'datetime',
    ];

    /**
     * @return Attribute<SitterPermissionSet, int>
     */
    protected function permissions(): Attribute
    {
        return Attribute::make(
            get: static fn (?int $value): SitterPermissionSet => SitterPermissionSet::fromInt((int) ($value ?? 0)),
            set: static function (SitterPermissionSet|array|int|null $value): int {
                if ($value instanceof SitterPermissionSet) {
                    return $value->toBitmask();
                }

                if (is_array($value)) {
                    return SitterPermissionSet::fromArray($value)->toBitmask();
                }

                return (int) ($value ?? 0);
            },
        );
    }

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

    public function allows(SitterPermission $permission): bool
    {
        return $this->permissions->allows($permission);
    }

    public function canFarm(): bool
    {
        return $this->permissions->canFarm();
    }

    public function canBuild(): bool
    {
        return $this->permissions->canBuild();
    }

    public function canSendTroops(): bool
    {
        return $this->permissions->canSendTroops();
    }

    public function canTrade(): bool
    {
        return $this->permissions->canTrade();
    }

    public function canSpendGold(): bool
    {
        return $this->permissions->canSpendGold();
    }

    public function permissionBitmask(): int
    {
        return $this->permissions->toBitmask();
    }

    /**
     * @return list<string>|null
     */
    public function permissionKeys(): ?array
    {
        if ($this->permissions->isFull()) {
            return null;
        }

        return $this->permissions->toArray();
    }

    /**
     * @return list<string>
     */
    public function effectivePermissionKeys(): array
    {
        if ($this->permissions->isFull()) {
            return SitterPermissionSet::full()->toArray();
        }

        return $this->permissions->toArray();
    }

    public function preset(): ?SitterPermissionPreset
    {
        if ($this->permissions->isFull()) {
            return SitterPermissionPreset::FullAccess;
        }

        return SitterPermissionPreset::detectFromPermissions($this->permissions);
    }

    public function isFullAccess(): bool
    {
        return $this->permissions->isFull();
    }
}
