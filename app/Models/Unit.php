<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Unit extends Model
{
    protected $table = 'units';

    protected $primaryKey = 'kid';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'kid' => 'integer',
        'race' => 'integer',
        'u1' => 'integer',
        'u2' => 'integer',
        'u3' => 'integer',
        'u4' => 'integer',
        'u5' => 'integer',
        'u6' => 'integer',
        'u7' => 'integer',
        'u8' => 'integer',
        'u9' => 'integer',
        'u10' => 'integer',
        'u11' => 'integer',
        'u99' => 'integer',
    ];

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'kid', 'kid');
    }

    public function scopeOfRace(Builder $query, int $race): Builder
    {
        return $query->where('race', $race);
    }

    public function scopeWithTroops(Builder $query): Builder
    {
        return $query->where(function (Builder $builder) {
            foreach (range(1, 11) as $slot) {
                $builder->orWhere("u{$slot}", '>', 0);
            }

            $builder->orWhere('u99', '>', 0);
        });
    }

    protected function troops(): Attribute
    {
        return Attribute::make(
            get: function (): array {
                $troops = [];
                foreach (range(1, 11) as $slot) {
                    $troops["u{$slot}"] = (int) ($this->attributes["u{$slot}"] ?? 0);
                }
                $troops['u99'] = (int) ($this->attributes['u99'] ?? 0);

                return $troops;
            }
        );
    }
}
