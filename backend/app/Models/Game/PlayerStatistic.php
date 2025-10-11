<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlayerStatistic extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'weekly_attack_points',
        'weekly_defense_points',
        'weekly_robber_points',
        'weekly_climber_points',
        'population',
    ];

    protected $casts = [
        'weekly_attack_points' => 'integer',
        'weekly_defense_points' => 'integer',
        'weekly_robber_points' => 'integer',
        'weekly_climber_points' => 'integer',
        'population' => 'integer',
    ];

    public function scopeOrderByMetric(Builder $query, string $metric): Builder
    {
        return $query->orderByDesc($metric)->orderBy('user_id');
    }
}
