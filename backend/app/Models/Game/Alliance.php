<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alliance extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'bonus_levels',
    ];

    protected $casts = [
        'bonus_levels' => 'array',
    ];

    public function incrementBonusLevel(string $type, int $targetLevel): void
    {
        $levels = $this->bonus_levels ?? [];
        $current = (int) ($levels[$type] ?? 0);
        if ($current >= $targetLevel) {
            return;
        }

        $levels[$type] = $targetLevel;
        $this->bonus_levels = $levels;
        $this->save();
    }
}
