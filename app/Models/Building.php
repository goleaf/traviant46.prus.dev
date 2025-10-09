<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Building extends Model
{
    protected $table = 'fdata';

    protected $primaryKey = 'kid';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'kid', 'kid');
    }

    public function scopeWithBuildingType(Builder $query, int $type): Builder
    {
        return $query->where(function (Builder $builder) use ($type) {
            foreach (range(1, 40) as $slot) {
                $builder->orWhere("f{$slot}t", $type);
            }
        });
    }

    public function scopeWithAnyLevelAbove(Builder $query, int $level): Builder
    {
        return $query->where(function (Builder $builder) use ($level) {
            foreach (range(1, 40) as $slot) {
                $builder->orWhere("f{$slot}", '>=', $level);
            }
        });
    }

    protected function slots(): Attribute
    {
        return Attribute::make(
            get: function (): array {
                $slots = [];
                foreach (range(1, 40) as $slot) {
                    $levelKey = "f{$slot}";
                    $typeKey = "f{$slot}t";
                    $slots[$slot] = [
                        'level' => (int) ($this->attributes[$levelKey] ?? 0),
                        'type' => (int) ($this->attributes[$typeKey] ?? 0),
                    ];
                }

                return $slots;
            }
        );
    }
}
