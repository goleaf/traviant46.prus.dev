<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Hero extends Model
{
    use HasFactory;

    protected $table = 'hero';

    protected $primaryKey = 'uid';

    public $incrementing = false;

    protected $keyType = 'int';

    public $timestamps = false;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'uid',
        'kid',
        'exp',
        'health',
        'itemHealth',
        'power',
        'offBonus',
        'defBonus',
        'production',
        'productionType',
        'lastupdate',
        'hide',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'uid' => 'integer',
        'kid' => 'integer',
        'exp' => 'integer',
        'health' => 'float',
        'itemHealth' => 'integer',
        'power' => 'integer',
        'offBonus' => 'integer',
        'defBonus' => 'integer',
        'production' => 'integer',
        'productionType' => 'integer',
        'lastupdate' => 'integer',
        'hide' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uid');
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'kid', 'kid');
    }

    public function homeVillage(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'kid', 'kid');
    }

    public function face(): HasOne
    {
        return $this->hasOne(HeroFace::class, 'uid', 'uid');
    }

    public function inventory(): HasOne
    {
        return $this->hasOne(HeroInventory::class, 'uid', 'uid');
    }

    public function items(): HasMany
    {
        return $this->hasMany(HeroItem::class, 'uid', 'uid');
    }

    public function adventures(): HasMany
    {
        return $this->hasMany(HeroAdventure::class, 'uid', 'uid');
    }

    public function accountEntries(): HasMany
    {
        return $this->hasMany(HeroAccountEntry::class, 'uid', 'uid');
    }

    protected function isAlive(): Attribute
    {
        return Attribute::get(fn (): bool => $this->health > 0);
    }

    protected function power(): Attribute
    {
        return Attribute::get(fn (): int => (int) $this->getAttributeValue('power'));
    }

    public function scopeAlive(Builder $query): Builder
    {
        return $query->where('health', '>', 0);
    }
}
