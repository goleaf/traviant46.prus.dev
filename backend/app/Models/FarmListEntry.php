<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FarmListEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_list_id',
        'target_village_id',
        'troops',
        'is_active',
    ];

    protected $casts = [
        'troops' => 'array',
        'is_active' => 'boolean',
    ];

    public function farmList(): BelongsTo
    {
        return $this->belongsTo(FarmList::class);
    }

    public function targetVillage(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'target_village_id');
    }
}
