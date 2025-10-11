<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AllianceBonusUpgrade extends Model
{
    use HasFactory;

    protected $fillable = [
        'alliance_id',
        'bonus_type',
        'target_level',
        'completes_at',
        'processed_at',
    ];

    protected $casts = [
        'target_level' => 'integer',
        'completes_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function scopeDue(Builder $query): Builder
    {
        return $query
            ->whereNull('processed_at')
            ->where('completes_at', '<=', now());
    }

    public function markProcessed(): void
    {
        $this->processed_at = now();
        $this->save();
    }
}
