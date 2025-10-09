<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable implements MustVerifyEmail
{
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
        return $this->hasMany(Village::class, 'owner_id');
    }

    public function hero(): HasOne
    {
        return $this->hasOne(Hero::class);
    }

    public function alliances(): BelongsToMany
    {
        return $this->belongsToMany(Alliance::class, 'alliance_user')
            ->withPivot(['role', 'permissions'])
            ->withTimestamps();
    }

    public function isAdmin(): bool
    {
        return (int) $this->legacy_uid === 0;
    }

    public function isMultihunter(): bool
    {
        return (int) $this->legacy_uid === 2;
    }
}
