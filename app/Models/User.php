<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'nickname',
        'email',
        'password',
        'faction',
        'language',
        'timezone',
        'gold',
        'silver',
        'settings',
        'profile',
        'is_active',
        'alliance_id',
        'active_village_id',
        'last_login_at',
    ];

    /**
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'settings' => 'array',
        'profile' => 'array',
        'is_active' => 'boolean',
        'gold' => 'integer',
        'silver' => 'integer',
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            $user->username = trim($user->username);
            $user->nickname ??= $user->username;
            $user->remember_token ??= Str::random(60);
        });
    }

    public function villages(): HasMany
    {
        return $this->hasMany(Village::class);
    }

    public function activeVillage(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'active_village_id');
    }

    public function alliance(): BelongsTo
    {
        return $this->belongsTo(Alliance::class);
    }

    public function hero(): HasOne
    {
        return $this->hasOne(Hero::class);
    }

    public function artifacts(): BelongsToMany
    {
        return $this->belongsToMany(Artifact::class)
            ->withPivot(['assigned_at'])
            ->withTimestamps();
    }

    public function attacks(): HasMany
    {
        return $this->hasMany(Attack::class, 'attacker_id');
    }

    public function defences(): HasMany
    {
        return $this->hasMany(Attack::class, 'defender_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function receivedMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    protected function displayName(): Attribute
    {
        return Attribute::get(fn (): string => $this->nickname ?: $this->username);
    }

    protected function password(): Attribute
    {
        return Attribute::set(function (string $value): string {
            return Hash::needsRehash($value) ? Hash::make($value) : $value;
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeWithAlliance(Builder $query): Builder
    {
        return $query->whereNotNull('alliance_id');
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        $likeTerm = '%' . Str::lower($term) . '%';

        return $query->where(function (Builder $builder) use ($likeTerm): void {
            $builder
                ->whereRaw('LOWER(username) LIKE ?', [$likeTerm])
                ->orWhereRaw('LOWER(nickname) LIKE ?', [$likeTerm])
                ->orWhereRaw('LOWER(email) LIKE ?', [$likeTerm]);
        });
    }
}
