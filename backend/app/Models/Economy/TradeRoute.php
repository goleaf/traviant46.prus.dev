<?php

namespace App\Models\Economy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradeRoute extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'origin_village_id',
        'target_village_id',
        'resources',
        'dispatch_interval_minutes',
        'next_dispatch_at',
        'is_active',
    ];

    protected $casts = [
        'resources' => 'array',
        'dispatch_interval_minutes' => 'integer',
        'next_dispatch_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeDue(Builder $query): Builder
    {
        return $query
            ->active()
            ->whereNotNull('next_dispatch_at')
            ->where('next_dispatch_at', '<=', now());
    }

    public function scheduleNextDispatch(): void
    {
        $interval = max(1, (int) $this->dispatch_interval_minutes);
        $this->next_dispatch_at = now()->addMinutes($interval);
    }
}
