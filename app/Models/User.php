<?php

namespace App\Models;

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

class User extends Authenticatable implements MustVerifyEmail
{
    private const TRIBE_NAMES = [
        1 => 'Romans',
        2 => 'Teutons',
        3 => 'Gauls',
        6 => 'Egyptians',
        7 => 'Huns',
    ];

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

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
        'sit1_uid',
        'sit2_uid',
        'last_owner_login_at',
        'last_login_at',
        'last_login_ip',
        'ban_reason',
        'ban_issued_at',
        'ban_expires_at',
        'is_banned',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
            'last_owner_login_at' => 'datetime',
            'last_login_at' => 'datetime',
            'ban_issued_at' => 'datetime',
            'ban_expires_at' => 'datetime',
            'is_banned' => 'bool',
        ];
    }

    public function sitterAssignments(): HasMany
    {
        return $this->hasMany(SitterAssignment::class, 'account_id');
    }

    public function sitterRoles(): HasMany
    {
        return $this->hasMany(SitterAssignment::class, 'sitter_id');
    }

    public function sitters(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'sitter_assignments', 'account_id', 'sitter_id')
            ->withPivot(['permissions', 'expires_at'])
            ->withTimestamps();
    }

    public function accountsDelegatedToMe(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'sitter_assignments', 'sitter_id', 'account_id')
            ->withPivot(['permissions', 'expires_at'])
            ->withTimestamps();
    }

    public function villages(): HasMany
    {
        return $this->hasMany(Village::class);
    }

    public function alliance(): BelongsTo
    {
        return $this->belongsTo(Alliance::class, 'current_alliance_id');
    }

    public function hero(): HasOne
    {
        return $this->hasOne(Hero::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
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

    public function isAdmin(): bool
    {
        return (int) $this->legacy_uid === 0;
    }

    public function isMultihunter(): bool
    {
        return (int) $this->legacy_uid === 2;
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
}
