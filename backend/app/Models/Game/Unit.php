<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

class Unit extends Model
{
    use HasFactory;

    protected $table = 'village_units';

    protected $fillable = [
        'village_id',
        'unit_type_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function unitType(): BelongsTo
    {
        return $this->belongsTo(UnitType::class);
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(Movement::class);
    }

    public function train(int $amount): static
    {
        if ($amount < 0) {
            throw new InvalidArgumentException('Cannot train a negative number of units.');
        }

        if ($amount === 0) {
            return $this;
        }

        $this->quantity = ($this->quantity ?? 0) + $amount;
        $this->save();

        return $this;
    }

    public function merge(self $other): static
    {
        if ($other->is($this)) {
            return $this;
        }

        if ($this->village_id !== $other->village_id || $this->unit_type_id !== $other->unit_type_id) {
            throw new InvalidArgumentException('Units must belong to the same village and unit type to merge.');
        }

        $this->quantity = ($this->quantity ?? 0) + ($other->quantity ?? 0);
        $this->save();

        $other->quantity = 0;
        $other->save();

        return $this;
    }

    public function split(int $amount): self
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Split amount must be greater than zero.');
        }

        $currentQuantity = $this->quantity ?? 0;

        if ($amount > $currentQuantity) {
            throw new InvalidArgumentException('Cannot split more units than are available.');
        }

        $this->quantity = $currentQuantity - $amount;
        $this->save();

        return static::query()->create([
            'village_id' => $this->village_id,
            'unit_type_id' => $this->unit_type_id,
            'quantity' => $amount,
        ]);
    }
}
