<?php

declare(strict_types=1);

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class World extends Model
{
    /** @use HasFactory<\Database\Factories\Game\WorldFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'speed',
        'features',
        'starts_at',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'speed' => 'float',
            'features' => 'array',
            'starts_at' => 'datetime',
        ];
    }
}
