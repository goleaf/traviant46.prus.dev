<?php

declare(strict_types=1);

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Oasis extends Model
{
    use HasFactory;

    protected $table = 'odata';

    protected $primaryKey = 'kid';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'type',
        'did',
        'wood',
        'iron',
        'clay',
        'crop',
        'lasttrain',
        'lastfarmed',
        'last_loyalty_update',
        'lastmupdate',
        'conquered_time',
        'loyalty',
        'owner',
    ];

    protected $casts = [
        'kid' => 'integer',
        'type' => 'integer',
        'did' => 'integer',
        'wood' => 'float',
        'iron' => 'float',
        'clay' => 'float',
        'crop' => 'float',
        'lasttrain' => 'integer',
        'lastfarmed' => 'integer',
        'last_loyalty_update' => 'integer',
        'lastmupdate' => 'integer',
        'conquered_time' => 'integer',
        'loyalty' => 'float',
        'owner' => 'integer',
    ];

    public function mapTile(): BelongsTo
    {
        return $this->belongsTo(MapTile::class, 'kid', 'id');
    }
}
