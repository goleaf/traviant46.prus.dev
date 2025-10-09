<?php

return [
    'building_types' => [
        'main_building' => [
            'name' => 'Main Building',
            'category' => 'infrastructure',
        ],
        'barracks' => [
            'name' => 'Barracks',
            'category' => 'military',
        ],
        'academy' => [
            'name' => 'Academy',
            'category' => 'military',
        ],
        'granary' => [
            'name' => 'Granary',
            'category' => 'resources',
        ],
        'warehouse' => [
            'name' => 'Warehouse',
            'category' => 'resources',
        ],
    ],

    'default_production' => [
        'wood' => 0,
        'clay' => 0,
        'iron' => 0,
        'crop' => 0,
    ],

    'hero' => [
        'base_attributes' => [
            'strength' => 0,
            'offense' => 0,
            'defense' => 0,
            'speed' => 0,
        ],
    ],

    'alliance' => [
        'default_roles' => ['leader', 'diplomat', 'quartermaster', 'member'],
    ],
];
