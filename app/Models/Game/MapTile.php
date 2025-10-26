<?php

declare(strict_types=1);

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MapTile extends Model
{
    use HasFactory;

    protected $table = 'wdata';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'x',
        'y',
        'fieldtype',
        'oasistype',
        'landscape',
        'crop_percent',
        'occupied',
        'map',
    ];

    protected $casts = [
        'x' => 'integer',
        'y' => 'integer',
        'fieldtype' => 'integer',
        'oasistype' => 'integer',
        'landscape' => 'integer',
        'crop_percent' => 'integer',
        'occupied' => 'boolean',
    ];

    public function oasis(): HasOne
    {
        return $this->hasOne(Oasis::class, 'kid', 'id');
    }
}
