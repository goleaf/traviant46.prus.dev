<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AvailableVillage extends Model
{
    use HasFactory;

    protected $table = 'available_villages';

    protected $primaryKey = 'kid';

    public $timestamps = false;

    protected $fillable = [
        'kid',
        'fieldtype',
        'r',
        'angle',
        'occupied',
        'rand',
    ];

    protected $casts = [
        'kid' => 'integer',
        'fieldtype' => 'integer',
        'r' => 'integer',
        'angle' => 'integer',
        'occupied' => 'boolean',
        'rand' => 'integer',
    ];

    public function tile(): BelongsTo
    {
        return $this->belongsTo(MapTile::class, 'kid', 'id');
    }

    public function scopeUnoccupied($query)
    {
        return $query->where('occupied', false);
    }
}
