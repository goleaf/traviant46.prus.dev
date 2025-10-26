<?php

declare(strict_types=1);

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Model;

class GameSummaryMetric extends Model
{
    protected $table = 'game_summary_metrics';

    protected $fillable = [
        'total_player_count',
        'roman_player_count',
        'teuton_player_count',
        'gaul_player_count',
        'egyptian_player_count',
        'hun_player_count',
        'first_village_player_name',
        'first_village_recorded_at',
        'first_artifact_player_name',
        'first_artifact_recorded_at',
        'first_world_wonder_plan_player_name',
        'first_world_wonder_plan_recorded_at',
        'first_world_wonder_player_name',
        'first_world_wonder_recorded_at',
    ];

    protected $casts = [
        'total_player_count' => 'int',
        'roman_player_count' => 'int',
        'teuton_player_count' => 'int',
        'gaul_player_count' => 'int',
        'egyptian_player_count' => 'int',
        'hun_player_count' => 'int',
        'first_village_recorded_at' => 'datetime',
        'first_artifact_recorded_at' => 'datetime',
        'first_world_wonder_plan_recorded_at' => 'datetime',
        'first_world_wonder_recorded_at' => 'datetime',
    ];
}
