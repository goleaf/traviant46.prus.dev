<?php

return [
    'daily_quests' => [
        'reset_interval_hours' => env('GAME_DAILY_QUEST_RESET_INTERVAL_HOURS', 24),
        'reset_chunk_size' => env('GAME_DAILY_QUEST_RESET_CHUNK', 500),
    ],

    'medals' => [
        'award_interval_hours' => env('GAME_MEDAL_INTERVAL_HOURS', 168),
        'top_limit' => env('GAME_MEDAL_TOP_LIMIT', 10),
        'categories' => [
            'attack' => 'weekly_attack_points',
            'defense' => 'weekly_defense_points',
            'robber' => 'weekly_robber_points',
            'climber' => 'weekly_climber_points',
        ],
    ],

    'alliance' => [
        'contribution_reset_interval_hours' => env('GAME_ALLIANCE_RESET_INTERVAL_HOURS', 24),
        'upgrade_chunk_size' => env('GAME_ALLIANCE_UPGRADE_CHUNK', 50),
    ],

    'artifacts' => [
        'default_effect_interval_minutes' => env('GAME_ARTIFACT_INTERVAL_MINUTES', 60),
        'processing_chunk_size' => env('GAME_ARTIFACT_CHUNK', 100),
    ],

    'wonder' => [
        'completion_level' => env('GAME_WONDER_COMPLETION_LEVEL', 100),
    ],
];
