<?php

declare(strict_types=1);

return [
    'chunk_size' => env('DATA_MIGRATION_CHUNK_SIZE', 500),

    'features' => [
        'attack_dispatches' => [
            'tables' => [
                [
                    'source' => 'a2b',
                    'target' => 'attack_dispatches',
                    'mode' => 'insert',
                    'chunk' => 250,
                    'transform' => [
                        App\Services\Migration\Transforms\AttackDispatchRowTransformer::class,
                        'transform',
                    ],
                ],
            ],
        ],

        'daily_quests' => [
            'tables' => [
                [
                    'source' => 'daily_quest',
                    'target' => 'daily_quest_progresses',
                    'mode' => 'upsert',
                    'primary_key' => 'uid',
                    'unique_by' => ['user_id'],
                    'chunk_by_id' => false,
                    'transform' => [
                        App\Services\Migration\Transforms\DailyQuestProgressRowTransformer::class,
                        'transform',
                    ],
                ],
            ],
        ],

        'login_ip_logs' => [
            'tables' => [
                [
                    'source' => 'log_ip',
                    'target' => 'login_ip_logs',
                    'mode' => 'insert',
                    'chunk' => 500,
                    'transform' => [
                        App\Services\Migration\Transforms\LoginIpLogRowTransformer::class,
                        'transform',
                    ],
                ],
            ],
        ],

        'game_summary_metrics' => [
            'tables' => [
                [
                    'source' => 'summary',
                    'target' => 'game_summary_metrics',
                    'mode' => 'insert_or_ignore',
                    'chunk' => 10,
                    'transform' => [
                        App\Services\Migration\Transforms\GameSummaryMetricRowTransformer::class,
                        'transform',
                    ],
                ],
            ],
        ],

        'world_casualty_snapshots' => [
            'tables' => [
                [
                    'source' => 'casualties',
                    'target' => 'world_casualty_snapshots',
                    'mode' => 'insert',
                    'chunk' => 250,
                    'transform' => [
                        App\Services\Migration\Transforms\WorldCasualtySnapshotRowTransformer::class,
                        'transform',
                    ],
                ],
            ],
        ],
    ],
];
