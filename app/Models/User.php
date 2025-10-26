<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StaffRole;
use App\Models\Game\Village as GameVillage;
use App\Notifications\QueuedPasswordResetNotification;
use App\Notifications\QueuedVerifyEmailNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable implements MustVerifyEmail
{
    private const TRIBE_NAMES = [
        1 => 'Romans',
        2 => 'Teutons',
        3 => 'Gauls',
        6 => 'Egyptians',
        7 => 'Huns',
    ];

    public const LEGACY_ADMIN_UID = 0;

    public const LEGACY_MULTIHUNTER_UID = 2;

    public const FIRST_PLAYER_LEGACY_UID = 1;

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'legacy_uid',
        'username',
        'name',
        'email',
        'password',
        'role',
        'race',
        'tribe',
        'current_alliance_id',
        'sit1_uid',
        'sit2_uid',
        'sitter_permission_matrix',
        'last_owner_login_at',
        'last_login_at',
        'last_login_ip',
        'last_login_ip_hash',
        'ban_reason',
        'ban_issued_at',
        'ban_expires_at',
        'is_banned',
        'beginner_protection_until',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => StaffRole::class,
            'last_owner_login_at' => 'datetime',
            'last_login_at' => 'datetime',
            'ban_issued_at' => 'datetime',
            'ban_expires_at' => 'datetime',
            'beginner_protection_until' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'is_banned' => 'bool',
            'sitter_permission_matrix' => 'array',
            'race' => 'integer',
            'tribe' => 'integer',
        ];
    }

    public function sitterAssignments(): HasMany
    {
        return $this->hasMany(SitterDelegation::class, 'owner_user_id');
    }

    public function sitterRoles(): HasMany
    {
        return $this->hasMany(SitterDelegation::class, 'sitter_user_id');
    }

    public function sitters(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'sitter_delegations', 'owner_user_id', 'sitter_user_id')
            ->withPivot(['permissions', 'expires_at', 'created_by', 'updated_by'])
            ->withTimestamps();
    }

    public function sittingFor(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'sitter_delegations', 'sitter_user_id', 'owner_user_id')
            ->withPivot(['permissions', 'expires_at', 'created_by', 'updated_by'])
            ->withTimestamps();
    }

    public function villages(): HasMany
    {
        return $this->hasMany(GameVillage::class);
    }

    public function alliance(): BelongsTo
    {
        return $this->belongsTo(Alliance::class, 'current_alliance_id');
    }

    public function allianceMembership(): HasOne
    {
        return $this->hasOne(AllianceMember::class);
    }

    public function hero(): HasOne
    {
        return $this->hasOne(Hero::class);
    }

    public function loginActivities(): HasMany
    {
        return $this->hasMany(LoginActivity::class);
    }

    public function loginIpLogs(): HasMany
    {
        return $this->hasMany(LoginIpLog::class);
    }

    public function dataExports(): HasMany
    {
        return $this->hasMany(UserDataExport::class);
    }

    public function deletionRequests(): HasMany
    {
        return $this->hasMany(AccountDeletionRequest::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function trustedDevices(): HasMany
    {
        return $this->hasMany(TrustedDevice::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(UserSession::class)->orderByDesc('last_activity_at');
    }

    protected function goldBalance(): Attribute
    {
        return Attribute::get(fn (): int => (int) ($this->attributes['gold_balance'] ?? $this->attributes['gold'] ?? 0));
    }

    protected function silverBalance(): Attribute
    {
        return Attribute::get(fn (): int => (int) ($this->attributes['silver_balance'] ?? $this->attributes['silver'] ?? 0));
    }

    protected function tribeName(): Attribute
    {
        return Attribute::get(function (): ?string {
            $tribe = $this->attributes['tribe'] ?? $this->attributes['race'] ?? null;

            if ($tribe === null) {
                return null;
            }

            $tribeId = is_numeric($tribe) ? (int) $tribe : null;

            if ($tribeId !== null) {
                return self::TRIBE_NAMES[$tribeId] ?? null;
            }

            return ucfirst((string) $tribe);
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_banned', false);
    }

    public function scopeBanned(Builder $query): Builder
    {
        return $query->where('is_banned', true);
    }

    public function scopeTribe(Builder $query, int|string $tribe): Builder
    {
        $tribeId = is_numeric($tribe)
            ? (int) $tribe
            : array_search(strtolower((string) $tribe), array_map('strtolower', self::TRIBE_NAMES), true);

        if ($tribeId === false || $tribeId === null) {
            return $query;
        }

        return $query->where('race', $tribeId);
    }

    /**
     * @return list<int>
     */
    public static function reservedLegacyUids(): array
    {
        return [
            self::LEGACY_ADMIN_UID,
            self::LEGACY_MULTIHUNTER_UID,
        ];
    }

    public static function isReservedLegacyUid(int $legacyUid): bool
    {
        return in_array($legacyUid, self::reservedLegacyUids(), true);
    }

    public function isAdmin(): bool
    {
        if ((int) $this->legacy_uid === self::LEGACY_ADMIN_UID) {
            return true;
        }

        return $this->hasStaffRole(StaffRole::Admin);
    }

    public function isMultihunter(): bool
    {
        return (int) $this->legacy_uid === self::LEGACY_MULTIHUNTER_UID;
    }

    public function staffRole(): StaffRole
    {
        $role = $this->role;

        if ($role instanceof StaffRole) {
            return $role;
        }

        $raw = is_string($role) ? $role : (string) ($this->attributes['role'] ?? StaffRole::Player->value);

        return StaffRole::tryFrom($raw) ?? StaffRole::Player;
    }

    public function hasStaffRole(StaffRole ...$roles): bool
    {
        if ($roles === []) {
            return false;
        }

        return in_array($this->staffRole(), $roles, true);
    }

    public function isBanned(): bool
    {
        if ((bool) ($this->attributes['is_banned'] ?? false)) {
            return true;
        }

        $expiresAt = $this->attributes['ban_expires_at'] ?? null;

        if ($expiresAt === null) {
            return false;
        }

        $expires = $this->asDateTime($expiresAt);

        return $expires->isFuture();
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new QueuedVerifyEmailNotification);
    }

    /**
     * @param mixed $token
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new QueuedPasswordResetNotification($token));
    }
}
