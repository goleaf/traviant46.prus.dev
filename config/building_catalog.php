<?php

declare(strict_types=1);

/*
 * Building catalog configuration enumerating economy structures, their
 * prerequisites, and growth curves for production bonuses or storage capacity.
 */

return [
    'sawmill' => [
        'name' => 'Sawmill',
        'prerequisites' => [
            ['type' => 'resource-field', 'slug' => 'woodcutter', 'name' => 'Woodcutter', 'level' => 10],
            ['type' => 'building', 'slug' => 'main-building', 'name' => 'Main Building', 'level' => 5],
        ],
        'bonuses_per_level' => [
            1 => 5,
            2 => 10,
            3 => 15,
            4 => 20,
            5 => 25,
        ],
    ],
    'grain-mill' => [
        'name' => 'Grain Mill',
        'prerequisites' => [
            ['type' => 'resource-field', 'slug' => 'cropland', 'name' => 'Cropland', 'level' => 5],
            ['type' => 'building', 'slug' => 'main-building', 'name' => 'Main Building', 'level' => 5],
        ],
        'bonuses_per_level' => [
            1 => 5,
            2 => 10,
            3 => 15,
            4 => 20,
            5 => 25,
        ],
    ],
    'iron-foundry' => [
        'name' => 'Iron Foundry',
        'prerequisites' => [
            ['type' => 'resource-field', 'slug' => 'iron-mine', 'name' => 'Iron Mine', 'level' => 10],
            ['type' => 'building', 'slug' => 'main-building', 'name' => 'Main Building', 'level' => 5],
        ],
        'bonuses_per_level' => [
            1 => 5,
            2 => 10,
            3 => 15,
            4 => 20,
            5 => 25,
        ],
    ],
    'bakery' => [
        'name' => 'Bakery',
        'prerequisites' => [
            ['type' => 'resource-field', 'slug' => 'cropland', 'name' => 'Cropland', 'level' => 10],
            ['type' => 'building', 'slug' => 'grain-mill', 'name' => 'Grain Mill', 'level' => 5],
            ['type' => 'building', 'slug' => 'main-building', 'name' => 'Main Building', 'level' => 5],
        ],
        'bonuses_per_level' => [
            1 => 5,
            2 => 10,
            3 => 15,
            4 => 20,
            5 => 25,
        ],
    ],
    'warehouse' => [
        'name' => 'Warehouse',
        'prerequisites' => [
            ['type' => 'building', 'slug' => 'main-building', 'name' => 'Main Building', 'level' => 1],
        ],
        'storage_capacity_per_level' => [
            1 => 1200,
            2 => 1700,
            3 => 2300,
            4 => 3100,
            5 => 4000,
            6 => 5000,
            7 => 6300,
            8 => 7800,
            9 => 9600,
            10 => 11800,
            11 => 14400,
            12 => 17600,
            13 => 21400,
            14 => 25900,
            15 => 31300,
            16 => 37900,
            17 => 45700,
            18 => 55100,
            19 => 66400,
            20 => 80000,
        ],
    ],
    'granary' => [
        'name' => 'Granary',
        'prerequisites' => [
            ['type' => 'building', 'slug' => 'main-building', 'name' => 'Main Building', 'level' => 1],
        ],
        'storage_capacity_per_level' => [
            1 => 1200,
            2 => 1700,
            3 => 2300,
            4 => 3100,
            5 => 4000,
            6 => 5000,
            7 => 6300,
            8 => 7800,
            9 => 9600,
            10 => 11800,
            11 => 14400,
            12 => 17600,
            13 => 21400,
            14 => 25900,
            15 => 31300,
            16 => 37900,
            17 => 45700,
            18 => 55100,
            19 => 66400,
            20 => 80000,
        ],
    ],
];
