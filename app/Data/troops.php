<?php

declare(strict_types=1);

return [
    'romans-legionnaire' => [
        'tribe' => 1,
        'base_time' => 1600,
        'population' => 1,
        'training' => [
            'options' => [
                0 => [
                    'building_id' => 19,
                    'level' => 1,
                    'ref' => 'barracks',
                ],
            ],
        ],
        'requirements' => [
            'buildings' => [
            ],
        ],
    ],
    'romans-praetorian' => [
        'tribe' => 1,
        'base_time' => 1760,
        'population' => 1,
        'training' => [
            'options' => [
                0 => [
                    'building_id' => 19,
                    'level' => 1,
                    'ref' => 'barracks',
                ],
            ],
        ],
        'requirements' => [
            'buildings' => [
                0 => [
                    'building_id' => 13,
                    'level' => 1,
                ],
                1 => [
                    'building_id' => 22,
                    'level' => 1,
                ],
            ],
        ],
    ],
    'romans-imperian' => [
        'tribe' => 1,
        'base_time' => 1920,
        'population' => 1,
        'training' => [
            'options' => [
                0 => [
                    'building_id' => 19,
                    'level' => 1,
                    'ref' => 'barracks',
                ],
            ],
        ],
        'requirements' => [
            'buildings' => [
                0 => [
                    'building_id' => 13,
                    'level' => 1,
                ],
                1 => [
                    'building_id' => 22,
                    'level' => 5,
                ],
            ],
        ],
    ],
    'romans-equites-legati' => [
        'tribe' => 1,
        'base_time' => 1360,
        'population' => 2,
        'training' => [
            'options' => [
                0 => [
                    'building_id' => 20,
                    'level' => 1,
                    'ref' => 'stable',
                ],
            ],
        ],
        'requirements' => [
            'buildings' => [
                0 => [
                    'building_id' => 22,
                    'level' => 5,
                ],
            ],
        ],
    ],
    'romans-equites-imperatoris' => [
        'tribe' => 1,
        'base_time' => 2640,
        'population' => 3,
        'training' => [
            'options' => [
                0 => [
                    'building_id' => 20,
                    'level' => 5,
                    'ref' => 'stable',
                ],
            ],
        ],
        'requirements' => [
            'buildings' => [
                0 => [
                    'building_id' => 22,
                    'level' => 5,
                ],
            ],
        ],
    ],
    'romans-equites-caesaris' => [
        'tribe' => 1,
        'base_time' => 3520,
        'population' => 4,
        'training' => [
            'options' => [
                0 => [
                    'building_id' => 20,
                    'level' => 10,
                    'ref' => 'stable',
                ],
            ],
        ],
        'requirements' => [
            'buildings' => [
                0 => [
                    'building_id' => 22,
                    'level' => 5,
                ],
            ],
        ],
    ],
    'romans-battering-ram' => [
        'tribe' => 1,
        'base_time' => 4600,
        'population' => 3,
        'training' => [
            'options' => [
                0 => [
                    'building_id' => 21,
                    'level' => 1,
                    'ref' => 'workshop',
                ],
            ],
        ],
        'requirements' => [
            'buildings' => [
                0 => [
                    'building_id' => 22,
                    'level' => 10,
                ],
            ],
        ],
    ],
    'romans-fire-catapult' => [
        'tribe' => 1,
        'base_time' => 9000,
        'population' => 6,
        'training' => [
            'options' => [
                0 => [
                    'building_id' => 21,
                    'level' => 10,
                    'ref' => 'workshop',
                ],
            ],
        ],
        'requirements' => [
            'buildings' => [
                0 => [
                    'building_id' => 22,
                    'level' => 15,
                ],
            ],
        ],
    ],
    'romans-senator' => [
        'tribe' => 1,
        'base_time' => 90700,
        'population' => 5,
        'training' => [
            'options' => [
                0 => [
                    'building_id' => 26,
                    'level' => 10,
                    'ref' => 'palace',
                ],
            ],
        ],
        'requirements' => [
            'buildings' => [
                0 => [
                    'building_id' => 16,
                    'level' => 10,
                ],
                1 => [
                    'building_id' => 22,
                    'level' => 20,
                ],
            ],
        ],
    ],
    'romans-settler' => [
        'tribe' => 1,
        'base_time' => 26900,
        'population' => 1,
        'training' => [
            'options' => [
                0 => [
                    'building_id' => 25,
                    'level' => 10,
                    'ref' => 'residence',
                ],
                1 => [
                    'building_id' => 26,
                    'level' => 10,
                    'ref' => 'palace',
                ],
                2 => [
                    'building_id' => 44,
                    'level' => 10,
                    'ref' => 'command_center',
                ],
            ],
        ],
        'requirements' => [
            'buildings' => [
            ],
        ],
    ],
    'teutons-clubswinger' => [
        'tribe' => 2,
        'base_time' => 720,
        'population' => 1,
        'training' => [
            'options' => [
                0 => [
                    'building_id' => 19,
                    'level' => 1,
                    'ref' => 'barracks',
                ],
            ],
        ],
        'requirements' => [
            'buildings' => [
            ],
        ],
    ],
    'teutons-spearman' => [
        'tribe' => 2,
        'base_time' => 1120,
        'population' => 1,
        'training' => [
            'options' => [
                0 => [
                    'building_id' => 19,
                    'level' => 1,
                    'ref' => 'barracks',
                ],
            ],
        ],
        'requirements' => [
            'buildings' => [
                0 => [
                    'building_id' => 22,
                    'level' => 1,
                ],
            ],
        ],
    ],
    'teutons-axeman' => [
        'tribe' => 2,
        'base_time' => 1200,
        'population' => 1,
        'training' => [
            'options' => [
                0 => [
                    'building_id' => 19,
                    'level' => 1,
                    'ref' => 'barracks',
                ],
            ],
        ],
        'requirements' => [
            'buildings' => [
                0 => [
                    'building_id' => 13,
                    'level' => 1,
                ],
                1 => [
                    'building_id' => 22,
                    'level' => 3,
                ],
            ],
        ],
    ],
    'teutons-scout' => [
        'tribe' => 2,
        'base_time' => 1120,
        'population' => 1,
        'training' => [
            'options' => [
                0 => [
                    'building_id' => 19,
                    'level' => 1,
                    'ref' => 'barracks',
                ],
            ],
        ],
        'requirements' => [
            'buildings' => [
                0 => [
                    'building_id' => 15,
                    'level' => 5,
                ],
                1 => [
                    'building_id' => 22,
                    'level' => 1,
                ],
            ],
        ],
    ],
    'teutons-paladin' => [
        'tribe' => 2,
        'base_time' => 2400,
        'population' => 2,
        'training' => [
            'options' => [
                0 => [
                    'building_id' => 20,
                    'level' => 3,
                    'ref' => 'stable',
                ],
            ],
        ],
        'requirements' => [
            'buildings' => [
                0 => [
                    'building_id' => 22,
                    'level' => 5,
                ],
            ],
        ],
    ],
    'teutons-teutonic-knight' => [
        'tribe' => 2,
        'base_time' => 2960,
        'population' => 3,
        'training' => [
            'options' => [
                0 => [
                    'building_id' => 20,
                    'level' => 10,
                    'ref' => 'stable',
                ],
            ],
        ],
        'requirements' => [
            'buildings' => [
                0 => [
                    'building_id' => 22,
                    'level' => 15,
                ],
            ],
        ],
    ],
    'teutons-ram' => [
        'tribe' => 2,
        'base_time' => 4200,
        'population' => 3,
        'training' => [
            'options' => [
                0 => [
                    'building_id' => 21,
                    'level' => 1,
                    'ref' => 'workshop',
                ],
            ],
        ],
        'requirements' => [
            'buildings' => [
                0 => [
                    'building_id' => 22,
                    'level' => 10,
                ],
            ],
        ],
    ],
    'teutons-catapult' => [
        'tribe' => 2,
        'base_time' => 9000,
        'population' => 6,
        'training' => [
            'options' => [
                0 => [
                    'building_id' => 21,
                    'level' => 10,
                    'ref' => 'workshop',
                ],
            ],
        ],
        'requirements' => [
            'buildings' => [
                0 => [
                    'building_id' => 22,
                    'level' => 15,
                ],
            ],
        ],
    ],
    'teutons-chief' => [
        'tribe' => 2,
        'base_time' => 70500,
        'population' => 4,
        'training' => [
            'options' => [
                0 => [
                    'building_id' => 26,
                    'level' => 10,
                    'ref' => 'palace',
                ],
            ],
        ],
        'requirements' => [
            'buildings' => [
                0 => [
                    'building_id' => 16,
                    'level' => 5,
                ],
                1 => [
                    'building_id' => 22,
                    'level' => 20,
                ],
            ],
        ],
    ],
    'teutons-settler' => [
        'tribe' => 2,
        'base_time' => 31000,
        'population' => 1,
        'training' => [
            'options' => [
                0 => [
                    'building_id' => 25,
                    'level' => 10,
                    'ref' => 'residence',
                ],
                1 => [
                    'building_id' => 26,
                    'level' => 10,
                    'ref' => 'palace',
                ],
                2 => [
                    'building_id' => 44,
                    'level' => 10,
                    'ref' => 'command_center',
                ],
            ],
        ],
        'requirements' => [
            'buildings' => [
            ],
        ],
    ],
    'gauls-phalanx' => [
        'tribe' => 3,
        'base_time' => 1040,
        'population' => 1,
        'training' => [
            'options' => [
                0 => [
                    'building_id' => 19,
                    'level' => 1,
                    'ref' => 'barracks',
                ],
            ],
        ],
        'requirements' => [
            'buildings' => [
            ],
        ],
    ],
    'gauls-swordsman' => [
        'tribe' => 3,
        'base_time' => 1440,
        'population' => 1,
        'training' => [
            'options' => [
                0 => [
                    'building_id' => 19,
                    'level' => 1,
                    'ref' => 'barracks',
                ],
            ],
        ],
        'requirements' => [
            'buildings' => [
                0 => [
                    'building_id' => 13,
                    'level' => 1,
                ],
                1 => [
                    'building_id' => 22,
                    'level' => 3,
                ],
            ],
        ],
    ],
    'gauls-pathfinder' => [
        'tribe' => 3,
        'base_time' => 1360,
        'population' => 2,
        'training' => [
            'options' => [
                0 => [
                    'building_id' => 20,
                    'level' => 1,
                    'ref' => 'stable',
                ],
            ],
        ],
        'requirements' => [
            'buildings' => [
                0 => [
                    'building_id' => 22,
                    'level' => 5,
                ],
            ],
        ],
    ],
    'gauls-theutates-thunder' => [
        'tribe' => 3,
        'base_time' => 2480,
        'population' => 2,
        'training' => [
            'options' => [
                0 => [
                    'building_id' => 20,
                    'level' => 3,
                    'ref' => 'stable',
                ],
            ],
        ],
        'requirements' => [
            'buildings' => [
                0 => [
                    'building_id' => 22,
                    'level' => 5,
                ],
            ],
        ],
    ],
    'gauls-druidrider' => [
        'tribe' => 3,
        'base_time' => 2560,
        'population' => 2,
        'training' => [
            'options' => [
                0 => [
                    'building_id' => 20,
                    'level' => 5,
                    'ref' => 'stable',
                ],
            ],
        ],
        'requirements' => [
            'buildings' => [
                0 => [
                    'building_id' => 22,
                    'level' => 5,
                ],
            ],
        ],
    ],
    'gauls-haeduan' => [
        'tribe' => 3,
        'base_time' => 3120,
        'population' => 3,
        'training' => [
            'options' => [
                0 => [
                    'building_id' => 20,
                    'level' => 10,
                    'ref' => 'stable',
                ],
            ],
        ],
        'requirements' => [
            'buildings' => [
                0 => [
                    'building_id' => 22,
                    'level' => 15,
                ],
            ],
        ],
    ],
    'gauls-ram' => [
        'tribe' => 3,
        'base_time' => 5000,
        'population' => 3,
        'training' => [
            'options' => [
                0 => [
                    'building_id' => 21,
                    'level' => 1,
                    'ref' => 'workshop',
                ],
            ],
        ],
        'requirements' => [
            'buildings' => [
                0 => [
                    'building_id' => 22,
                    'level' => 10,
                ],
            ],
        ],
    ],
    'gauls-trebuchet' => [
        'tribe' => 3,
        'base_time' => 9000,
        'population' => 6,
        'training' => [
            'options' => [
                0 => [
                    'building_id' => 21,
                    'level' => 10,
                    'ref' => 'workshop',
                ],
            ],
        ],
        'requirements' => [
            'buildings' => [
                0 => [
                    'building_id' => 22,
                    'level' => 15,
                ],
            ],
        ],
    ],
    'gauls-chieftain' => [
        'tribe' => 3,
        'base_time' => 90700,
        'population' => 4,
        'training' => [
            'options' => [
                0 => [
                    'building_id' => 26,
                    'level' => 10,
                    'ref' => 'palace',
                ],
            ],
        ],
        'requirements' => [
            'buildings' => [
                0 => [
                    'building_id' => 16,
                    'level' => 10,
                ],
                1 => [
                    'building_id' => 22,
                    'level' => 20,
                ],
            ],
        ],
    ],
    'gauls-settler' => [
        'tribe' => 3,
        'base_time' => 22700,
        'population' => 1,
        'training' => [
            'options' => [
                0 => [
                    'building_id' => 25,
                    'level' => 10,
                    'ref' => 'residence',
                ],
                1 => [
                    'building_id' => 26,
                    'level' => 10,
                    'ref' => 'palace',
                ],
                2 => [
                    'building_id' => 44,
                    'level' => 10,
                    'ref' => 'command_center',
                ],
            ],
        ],
        'requirements' => [
            'buildings' => [
            ],
        ],
    ],
    'egyptians-slave-militia' => [
        'tribe' => 6,
        'base_time' => 530,
        'population' => 1,
        'training' => [
            'options' => [
                0 => [
                    'building_id' => 19,
                    'level' => 1,
                    'ref' => 'barracks',
                ],
            ],
        ],
        'requirements' => [
            'buildings' => [
            ],
        ],
    ],
    'egyptians-ash-warden' => [
        'tribe' => 6,
        'base_time' => 1320,
        'population' => 1,
        'training' => [
            'options' => [
                0 => [
                    'building_id' => 19,
                    'level' => 1,
                    'ref' => 'barracks',
                ],
            ],
        ],
        'requirements' => [
            'buildings' => [
                0 => [
                    'building_id' => 13,
                    'level' => 1,
                ],
            ],
        ],
    ],
    'egyptians-khopesh-warrior' => [
        'tribe' => 6,
        'base_time' => 1440,
        'population' => 1,
        'training' => [
            'options' => [
                0 => [
                    'building_id' => 19,
                    'level' => 1,
                    'ref' => 'barracks',
                ],
            ],
        ],
        'requirements' => [
            'buildings' => [
                0 => [
                    'building_id' => 13,
                    'level' => 1,
                ],
                1 => [
                    'building_id' => 22,
                    'level' => 5,
                ],
            ],
        ],
    ],
    'egyptians-sopdu-explorer' => [
        'tribe' => 6,
        'base_time' => 1360,
        'population' => 2,
        'training' => [
            'options' => [
                0 => [
                    'building_id' => 20,
                    'level' => 1,
                    'ref' => 'stable',
                ],
            ],
        ],
        'requirements' => [
            'buildings' => [
                0 => [
                    'building_id' => 22,
                    'level' => 5,
                ],
            ],
        ],
    ],
    'egyptians-anhur-guard' => [
        'tribe' => 6,
        'base_time' => 2560,
        'population' => 2,
        'training' => [
            'options' => [
                0 => [
                    'building_id' => 20,
                    'level' => 5,
                    'ref' => 'stable',
                ],
            ],
        ],
        'requirements' => [
            'buildings' => [
                0 => [
                    'building_id' => 22,
                    'level' => 5,
                ],
            ],
        ],
    ],
    'egyptians-resheph-chariot' => [
        'tribe' => 6,
        'base_time' => 3240,
        'population' => 3,
        'training' => [
            'options' => [
                0 => [
                    'building_id' => 20,
                    'level' => 10,
                    'ref' => 'stable',
                ],
            ],
        ],
        'requirements' => [
            'buildings' => [
                0 => [
                    'building_id' => 22,
                    'level' => 5,
                ],
            ],
        ],
    ],
    'egyptians-ram' => [
        'tribe' => 6,
        'base_time' => 4800,
        'population' => 3,
        'training' => [
            'options' => [
                0 => [
                    'building_id' => 21,
                    'level' => 5,
                    'ref' => 'workshop',
                ],
            ],
        ],
        'requirements' => [
            'buildings' => [
                0 => [
                    'building_id' => 22,
                    'level' => 10,
                ],
            ],
        ],
    ],
    'egyptians-stone-catapult' => [
        'tribe' => 6,
        'base_time' => 9000,
        'population' => 6,
        'training' => [
            'options' => [
                0 => [
                    'building_id' => 21,
                    'level' => 10,
                    'ref' => 'workshop',
                ],
            ],
        ],
        'requirements' => [
            'buildings' => [
                0 => [
                    'building_id' => 22,
                    'level' => 15,
                ],
            ],
        ],
    ],
    'egyptians-nomarch' => [
        'tribe' => 6,
        'base_time' => 90700,
        'population' => 4,
        'training' => [
            'options' => [
                0 => [
                    'building_id' => 26,
                    'level' => 10,
                    'ref' => 'palace',
                ],
                1 => [
                    'building_id' => 44,
                    'level' => 10,
                    'ref' => 'command_center',
                ],
            ],
        ],
        'requirements' => [
            'buildings' => [
                0 => [
                    'building_id' => 16,
                    'level' => 10,
                ],
                1 => [
                    'building_id' => 22,
                    'level' => 20,
                ],
            ],
        ],
    ],
    'egyptians-settler' => [
        'tribe' => 6,
        'base_time' => 24800,
        'population' => 1,
        'training' => [
            'options' => [
                0 => [
                    'building_id' => 25,
                    'level' => 10,
                    'ref' => 'residence',
                ],
                1 => [
                    'building_id' => 26,
                    'level' => 10,
                    'ref' => 'palace',
                ],
                2 => [
                    'building_id' => 44,
                    'level' => 10,
                    'ref' => 'command_center',
                ],
            ],
        ],
        'requirements' => [
            'buildings' => [
            ],
        ],
    ],
    'huns-mercenary' => [
        'tribe' => 7,
        'base_time' => 810,
        'population' => 1,
        'training' => [
            'options' => [
                0 => [
                    'building_id' => 19,
                    'level' => 1,
                    'ref' => 'barracks',
                ],
            ],
        ],
        'requirements' => [
            'buildings' => [
            ],
        ],
    ],
    'huns-bowman' => [
        'tribe' => 7,
        'base_time' => 1120,
        'population' => 1,
        'training' => [
            'options' => [
                0 => [
                    'building_id' => 19,
                    'level' => 1,
                    'ref' => 'barracks',
                ],
            ],
        ],
        'requirements' => [
            'buildings' => [
                0 => [
                    'building_id' => 13,
                    'level' => 1,
                ],
                1 => [
                    'building_id' => 22,
                    'level' => 3,
                ],
            ],
        ],
    ],
    'huns-spotter' => [
        'tribe' => 7,
        'base_time' => 1360,
        'population' => 2,
        'training' => [
            'options' => [
                0 => [
                    'building_id' => 20,
                    'level' => 1,
                    'ref' => 'stable',
                ],
            ],
        ],
        'requirements' => [
            'buildings' => [
                0 => [
                    'building_id' => 22,
                    'level' => 5,
                ],
            ],
        ],
    ],
    'huns-steppe-rider' => [
        'tribe' => 7,
        'base_time' => 2400,
        'population' => 2,
        'training' => [
            'options' => [
                0 => [
                    'building_id' => 20,
                    'level' => 3,
                    'ref' => 'stable',
                ],
            ],
        ],
        'requirements' => [
            'buildings' => [
                0 => [
                    'building_id' => 22,
                    'level' => 5,
                ],
            ],
        ],
    ],
    'huns-marksman' => [
        'tribe' => 7,
        'base_time' => 2480,
        'population' => 2,
        'training' => [
            'options' => [
                0 => [
                    'building_id' => 20,
                    'level' => 5,
                    'ref' => 'stable',
                ],
            ],
        ],
        'requirements' => [
            'buildings' => [
                0 => [
                    'building_id' => 22,
                    'level' => 5,
                ],
            ],
        ],
    ],
    'huns-marauder' => [
        'tribe' => 7,
        'base_time' => 2990,
        'population' => 3,
        'training' => [
            'options' => [
                0 => [
                    'building_id' => 20,
                    'level' => 10,
                    'ref' => 'stable',
                ],
            ],
        ],
        'requirements' => [
            'buildings' => [
                0 => [
                    'building_id' => 22,
                    'level' => 15,
                ],
            ],
        ],
    ],
    'huns-ram' => [
        'tribe' => 7,
        'base_time' => 4400,
        'population' => 3,
        'training' => [
            'options' => [
                0 => [
                    'building_id' => 21,
                    'level' => 1,
                    'ref' => 'workshop',
                ],
            ],
        ],
        'requirements' => [
            'buildings' => [
                0 => [
                    'building_id' => 22,
                    'level' => 10,
                ],
            ],
        ],
    ],
    'huns-catapult' => [
        'tribe' => 7,
        'base_time' => 9000,
        'population' => 6,
        'training' => [
            'options' => [
                0 => [
                    'building_id' => 21,
                    'level' => 10,
                    'ref' => 'workshop',
                ],
            ],
        ],
        'requirements' => [
            'buildings' => [
                0 => [
                    'building_id' => 22,
                    'level' => 15,
                ],
            ],
        ],
    ],
    'huns-logades' => [
        'tribe' => 7,
        'base_time' => 90700,
        'population' => 4,
        'training' => [
            'options' => [
                0 => [
                    'building_id' => 26,
                    'level' => 10,
                    'ref' => 'palace',
                ],
                1 => [
                    'building_id' => 44,
                    'level' => 10,
                    'ref' => 'command_center',
                ],
            ],
        ],
        'requirements' => [
            'buildings' => [
                0 => [
                    'building_id' => 16,
                    'level' => 10,
                ],
                1 => [
                    'building_id' => 22,
                    'level' => 20,
                ],
            ],
        ],
    ],
    'huns-settler' => [
        'tribe' => 7,
        'base_time' => 28950,
        'population' => 1,
        'training' => [
            'options' => [
                0 => [
                    'building_id' => 25,
                    'level' => 10,
                    'ref' => 'residence',
                ],
                1 => [
                    'building_id' => 26,
                    'level' => 10,
                    'ref' => 'palace',
                ],
                2 => [
                    'building_id' => 44,
                    'level' => 10,
                    'ref' => 'command_center',
                ],
            ],
        ],
        'requirements' => [
            'buildings' => [
            ],
        ],
    ],
];
