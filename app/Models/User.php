<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;

    protected $table = 'users';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'name',
        'email',
        'password',
        'aid',
        'race',
        'access',
        'plus',
        'b1',
        'b2',
        'b3',
        'b4',
        'gender',
        'birthday',
        'location',
        'language',
        'desc1',
        'desc2',
        'note',
        'allianceSettings',
        'autoComplete',
        'display',
        'timezone',
        'mapMarkSettings',
        'lastReturn',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'aid' => 'integer',
        'race' => 'integer',
        'access' => 'integer',
        'email_verified' => 'boolean',
        'plus' => 'integer',
        'b1' => 'integer',
        'b2' => 'integer',
        'b3' => 'integer',
        'b4' => 'integer',
        'goldclub' => 'boolean',
        'escape' => 'boolean',
        'allianceNotificationEnabled' => 'boolean',
        'pending_training_alliance_bonus_unlock_animation' => 'boolean',
        'pending_armor_alliance_bonus_unlock_animation' => 'boolean',
        'pending_cp_alliance_bonus_unlock_animation' => 'boolean',
        'pending_trade_alliance_bonus_unlock_animation' => 'boolean',
        'lastReturn' => 'integer',
    ];

    public function villages(): HasMany
    {
        return $this->hasMany(Village::class, 'owner', 'id');
    }

    public function capitalVillage(): HasOne
    {
        return $this->hasOne(Village::class, 'owner', 'id')->where('capital', true);
    }

    public function alliance(): BelongsTo
    {
        return $this->belongsTo(Alliance::class, 'aid', 'id');
    }

    public function hero(): HasOne
    {
        return $this->hasOne(Hero::class, 'uid', 'id');
    }

    public function artifacts(): HasMany
    {
        return $this->hasMany(Artifact::class, 'uid', 'id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'uid', 'id');
    }

    public function messagesSent(): HasMany
    {
        return $this->hasMany(Message::class, 'uid', 'id');
    }

    public function messagesReceived(): HasMany
    {
        return $this->hasMany(Message::class, 'to_uid', 'id');
    }

    public function attacksLaunched(): HasManyThrough
    {
        return $this->hasManyThrough(
            Attack::class,
            Village::class,
            'owner',
            'kid',
            'id',
            'kid'
        );
    }

    public function attacksIncoming(): HasManyThrough
    {
        return $this->hasManyThrough(
            Attack::class,
            Village::class,
            'owner',
            'to_kid',
            'id',
            'kid'
        );
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('access', 1);
    }

    public function scopeWithAlliance(Builder $query, int $allianceId): Builder
    {
        return $query->where('aid', $allianceId);
    }

    public function scopeRecentlyOnline(Builder $query, Carbon $since): Builder
    {
        return $query->where('lastReturn', '>=', $since->getTimestamp());
    }

    protected function allianceSettings(): Attribute
    {
        return Attribute::make(
            get: static fn (?string $value): array => $value === null
                ? []
                : array_values(array_filter(
                    array_map('trim', explode('|', $value)),
                    static fn (string $segment): bool => $segment !== ''
                )),
            set: static fn ($value): array => [
                'allianceSettings' => implode(
                    '|',
                    array_map('strval', is_array($value) ? $value : [$value])
                ),
            ]
        );
    }

    protected function isActive(): Attribute
    {
        return Attribute::make(
            get: fn ($value, array $attributes): bool => ((int) ($attributes['access'] ?? 0)) === 1
        );
    }

    protected function lastReturnAt(): Attribute
    {
        return Attribute::make(
            get: fn ($value, array $attributes): ?Carbon => isset($attributes['lastReturn']) && (int) $attributes['lastReturn'] > 0
                ? Carbon::createFromTimestamp((int) $attributes['lastReturn'])
                : null,
            set: fn ($value): array => [
                'lastReturn' => $value instanceof Carbon ? $value->getTimestamp() : (int) $value,
            ]
        );
    }
}
