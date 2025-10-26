<?php

declare(strict_types=1);

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VillageUnit extends Model
{
    use HasFactory;

    protected $fillable = [
        'village_id',
        'unit_type_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }

    public function unitType(): BelongsTo
    {
        return $this->belongsTo(TroopType::class, 'unit_type_id');
    }

    public function incrementQuantity(int $amount): void
    {
        $this->quantity = ($this->quantity ?? 0) + $amount;
        $this->save();
    }
}
