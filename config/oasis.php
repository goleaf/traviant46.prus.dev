<?php

declare(strict_types=1);

return [
    'default_respawn_minutes' => 360,
    'presets' => [
        1 => [
            'label' => 'wood',
            'respawn_minutes' => 180,
            'garrison' => [
                'rat' => 25,
                'spider' => 18,
                'boar' => 12,
                'wolf' => 5,
                'bear' => 2,
            ],
        ],
        2 => [
            'label' => 'clay',
            'respawn_minutes' => 240,
            'garrison' => [
                'rat' => 22,
                'spider' => 20,
                'snake' => 18,
                'bat' => 10,
                'boar' => 5,
            ],
        ],
        3 => [
            'label' => 'iron',
            'respawn_minutes' => 300,
            'garrison' => [
                'rat' => 18,
                'spider' => 16,
                'snake' => 20,
                'bat' => 16,
                'bear' => 12,
                'crocodile' => 8,
            ],
        ],
        4 => [
            'label' => 'crop',
            'respawn_minutes' => 360,
            'garrison' => [
                'rat' => 20,
                'spider' => 20,
                'boar' => 24,
                'wolf' => 20,
                'bear' => 18,
                'tiger' => 12,
                'elephant' => 6,
            ],
        ],
    ],
];
