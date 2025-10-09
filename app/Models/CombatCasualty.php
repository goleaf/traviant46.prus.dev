<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CombatCasualty extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'combat_casualties';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'attacks_count',
        'casualties_count',
        'recorded_for',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'attacks_count' => 'integer',
        'casualties_count' => 'integer',
        'recorded_for' => 'datetime',
    ];
}
