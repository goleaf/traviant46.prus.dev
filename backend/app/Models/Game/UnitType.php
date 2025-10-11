<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UnitType extends Model
{
    use HasFactory;

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }
}
