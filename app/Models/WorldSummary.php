<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorldSummary extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'world_summaries';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'player_count',
        'roman_player_count',
        'teuton_player_count',
        'gaul_player_count',
        'egyptian_player_count',
        'hun_player_count',
        'first_village_player_id',
        'first_village_player_name',
        'first_village_recorded_at',
        'first_artifact_player_id',
        'first_artifact_player_name',
        'first_artifact_recorded_at',
        'first_plan_player_id',
        'first_plan_player_name',
        'first_plan_recorded_at',
        'first_wonder_player_id',
        'first_wonder_player_name',
        'first_wonder_recorded_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'player_count' => 'integer',
        'roman_player_count' => 'integer',
        'teuton_player_count' => 'integer',
        'gaul_player_count' => 'integer',
        'egyptian_player_count' => 'integer',
        'hun_player_count' => 'integer',
        'first_village_recorded_at' => 'datetime',
        'first_artifact_recorded_at' => 'datetime',
        'first_plan_recorded_at' => 'datetime',
        'first_wonder_recorded_at' => 'datetime',
    ];

    public function firstVillagePlayer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'first_village_player_id');
    }

    public function firstArtifactPlayer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'first_artifact_player_id');
    }

    public function firstPlanPlayer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'first_plan_player_id');
    }

    public function firstWonderPlayer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'first_wonder_player_id');
    }
}
