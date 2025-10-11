<?php

return [
    'starvation' => [
        'enabled' => env('GAME_STARVATION_ENABLED', true),
    ],

    'units' => [
        'crop_consumption' => [
            // Default Travian-style crop consumption per unit type. Override via env or config if needed.
            1 => 1,
            2 => 1,
            3 => 1,
            4 => 1,
            5 => 2,
            6 => 3,
            7 => 3,
            8 => 3,
            9 => 6,
            10 => 5,
            11 => 6, // hero placeholder
        ],
    ],
];
