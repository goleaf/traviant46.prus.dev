<?php

declare(strict_types=1);

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $tribe
 * @property string $code
 * @property string $name
 * @property int $attack
 * @property int $def_inf
 * @property int $def_cav
 * @property int $speed
 * @property int $carry
 * @property array<string, int> $train_cost
 * @property int $upkeep
 */
class TroopType extends Model
{
    use HasFactory;

    protected $fillable = [
        'tribe',
        'code',
        'name',
        'attack',
        'def_inf',
        'def_cav',
        'speed',
        'carry',
        'train_cost',
        'upkeep',
    ];

    protected $casts = [
        'tribe' => 'integer',
        'attack' => 'integer',
        'def_inf' => 'integer',
        'def_cav' => 'integer',
        'speed' => 'integer',
        'carry' => 'integer',
        'train_cost' => 'array',
        'upkeep' => 'integer',
    ];
}
