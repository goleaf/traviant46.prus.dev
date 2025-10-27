<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ArtifactSeeder extends Seeder
{
    /**
     * @var list<array{
     *     code: string,
     *     name: string,
     *     size: 'small'|'large'|'unique',
     *     scope: 'village'|'account',
     *     treasury_level_req: int,
     *     effect: array{
     *         category: string,
     *         summary: string,
     *         modifiers: list<array<string, mixed>>,
     *         notes?: list<string>
     *     }
     * }>
     */
    private const ARTIFACTS = [
        [
            'code' => 'architects-secret-small',
            'name' => "The architects' slight secret",
            'size' => 'small',
            'scope' => 'village',
            'treasury_level_req' => 10,
            'effect' => [
                'category' => 'architects-secret',
                'summary' => 'Buildings in this village are 4× harder to destroy with siege weapons.',
                'modifiers' => [
                    [
                        'type' => 'building_durability',
                        'operation' => 'multiply',
                        'value' => 4.0,
                        'applies_to' => 'village_buildings',
                    ],
                ],
                'notes' => [
                    'Does not extend to Wonder of the World structures.',
                ],
            ],
        ],
        [
            'code' => 'architects-secret-large',
            'name' => "The architects' great secret",
            'size' => 'large',
            'scope' => 'account',
            'treasury_level_req' => 20,
            'effect' => [
                'category' => 'architects-secret',
                'summary' => 'All villages owned by this account gain 3× building durability.',
                'modifiers' => [
                    [
                        'type' => 'building_durability',
                        'operation' => 'multiply',
                        'value' => 3.0,
                        'applies_to' => 'account_buildings',
                    ],
                ],
                'notes' => [
                    'Wonder of the World durability is not affected.',
                ],
            ],
        ],
        [
            'code' => 'architects-secret-unique',
            'name' => 'The architects unique secret',
            'size' => 'unique',
            'scope' => 'account',
            'treasury_level_req' => 20,
            'effect' => [
                'category' => 'architects-secret',
                'summary' => 'Account-wide siege resistance increased by 5×.',
                'modifiers' => [
                    [
                        'type' => 'building_durability',
                        'operation' => 'multiply',
                        'value' => 5.0,
                        'applies_to' => 'account_buildings',
                    ],
                ],
                'notes' => [
                    'WW villages remain exempt from the modifier.',
                ],
            ],
        ],
        [
            'code' => 'titan-boots-small',
            'name' => 'The slight titan boots',
            'size' => 'small',
            'scope' => 'village',
            'treasury_level_req' => 10,
            'effect' => [
                'category' => 'titan-boots',
                'summary' => 'Troops originating from this village march 2× faster.',
                'modifiers' => [
                    [
                        'type' => 'movement_speed',
                        'operation' => 'multiply',
                        'value' => 2.0,
                        'applies_to' => 'village_troops',
                    ],
                ],
            ],
        ],
        [
            'code' => 'titan-boots-large',
            'name' => 'The great titan boots',
            'size' => 'large',
            'scope' => 'account',
            'treasury_level_req' => 20,
            'effect' => [
                'category' => 'titan-boots',
                'summary' => 'All troops owned by this account move 1.5× faster.',
                'modifiers' => [
                    [
                        'type' => 'movement_speed',
                        'operation' => 'multiply',
                        'value' => 1.5,
                        'applies_to' => 'account_troops',
                    ],
                ],
            ],
        ],
        [
            'code' => 'titan-boots-unique',
            'name' => 'The unique titan boots',
            'size' => 'unique',
            'scope' => 'account',
            'treasury_level_req' => 20,
            'effect' => [
                'category' => 'titan-boots',
                'summary' => 'Account-wide troop movement speed doubles.',
                'modifiers' => [
                    [
                        'type' => 'movement_speed',
                        'operation' => 'multiply',
                        'value' => 2.0,
                        'applies_to' => 'account_troops',
                    ],
                ],
            ],
        ],
        [
            'code' => 'eagle-eyes-small',
            'name' => 'The eagles slight eyes',
            'size' => 'small',
            'scope' => 'village',
            'treasury_level_req' => 10,
            'effect' => [
                'category' => 'eagle-eyes',
                'summary' => 'Scouts in this village are 5× more effective and reveal incoming troop types.',
                'modifiers' => [
                    [
                        'type' => 'scouting_strength',
                        'operation' => 'multiply',
                        'value' => 5.0,
                        'applies_to' => 'village_scouts',
                    ],
                    [
                        'type' => 'rally_point_visibility',
                        'operation' => 'enhance',
                        'applies_to' => 'incoming_troop_types',
                    ],
                ],
            ],
        ],
        [
            'code' => 'eagle-eyes-large',
            'name' => 'The eagles great eyes',
            'size' => 'large',
            'scope' => 'account',
            'treasury_level_req' => 20,
            'effect' => [
                'category' => 'eagle-eyes',
                'summary' => 'All scouts owned by this account gain a 3× strength boost and reveal incoming troop types.',
                'modifiers' => [
                    [
                        'type' => 'scouting_strength',
                        'operation' => 'multiply',
                        'value' => 3.0,
                        'applies_to' => 'account_scouts',
                    ],
                    [
                        'type' => 'rally_point_visibility',
                        'operation' => 'enhance',
                        'applies_to' => 'incoming_troop_types',
                    ],
                ],
            ],
        ],
        [
            'code' => 'eagle-eyes-unique',
            'name' => 'The eagles unique eyes',
            'size' => 'unique',
            'scope' => 'account',
            'treasury_level_req' => 20,
            'effect' => [
                'category' => 'eagle-eyes',
                'summary' => 'Account-wide scout actions are 10× stronger and always expose incoming troop types.',
                'modifiers' => [
                    [
                        'type' => 'scouting_strength',
                        'operation' => 'multiply',
                        'value' => 10.0,
                        'applies_to' => 'account_scouts',
                    ],
                    [
                        'type' => 'rally_point_visibility',
                        'operation' => 'enhance',
                        'applies_to' => 'incoming_troop_types',
                    ],
                ],
            ],
        ],
        [
            'code' => 'diet-control-small',
            'name' => 'Slight diet control',
            'size' => 'small',
            'scope' => 'village',
            'treasury_level_req' => 10,
            'effect' => [
                'category' => 'diet-control',
                'summary' => 'Crop consumption for all stationed troops in this village is reduced by 50%.',
                'modifiers' => [
                    [
                        'type' => 'crop_upkeep',
                        'operation' => 'multiply',
                        'value' => 0.5,
                        'applies_to' => 'village_troops',
                    ],
                ],
            ],
        ],
        [
            'code' => 'diet-control-large',
            'name' => 'Great diet control',
            'size' => 'large',
            'scope' => 'account',
            'treasury_level_req' => 20,
            'effect' => [
                'category' => 'diet-control',
                'summary' => 'All villages owned by this account reduce troop crop usage by 25%.',
                'modifiers' => [
                    [
                        'type' => 'crop_upkeep',
                        'operation' => 'multiply',
                        'value' => 0.75,
                        'applies_to' => 'account_troops',
                    ],
                ],
            ],
        ],
        [
            'code' => 'diet-control-unique',
            'name' => 'Unique diet control',
            'size' => 'unique',
            'scope' => 'account',
            'treasury_level_req' => 20,
            'effect' => [
                'category' => 'diet-control',
                'summary' => 'Account-wide crop usage is halved for stationed and reinforcing troops.',
                'modifiers' => [
                    [
                        'type' => 'crop_upkeep',
                        'operation' => 'multiply',
                        'value' => 0.5,
                        'applies_to' => 'account_troops',
                    ],
                ],
            ],
        ],
        [
            'code' => 'trainers-talent-small',
            'name' => 'The trainers slight talent',
            'size' => 'small',
            'scope' => 'village',
            'treasury_level_req' => 10,
            'effect' => [
                'category' => 'trainers-talent',
                'summary' => 'Barracks, stable, workshop, palace, and residence in this village train troops 50% faster.',
                'modifiers' => [
                    [
                        'type' => 'training_time',
                        'operation' => 'multiply',
                        'value' => 0.5,
                        'applies_to' => 'village_training_buildings',
                    ],
                ],
            ],
        ],
        [
            'code' => 'trainers-talent-large',
            'name' => 'The trainers great talent',
            'size' => 'large',
            'scope' => 'account',
            'treasury_level_req' => 20,
            'effect' => [
                'category' => 'trainers-talent',
                'summary' => 'All account training buildings operate at 75% of their normal time.',
                'modifiers' => [
                    [
                        'type' => 'training_time',
                        'operation' => 'multiply',
                        'value' => 0.75,
                        'applies_to' => 'account_training_buildings',
                    ],
                ],
            ],
        ],
        [
            'code' => 'trainers-talent-unique',
            'name' => 'The trainers unique talent',
            'size' => 'unique',
            'scope' => 'account',
            'treasury_level_req' => 20,
            'effect' => [
                'category' => 'trainers-talent',
                'summary' => 'Account-wide troop training time is reduced by 50%.',
                'modifiers' => [
                    [
                        'type' => 'training_time',
                        'operation' => 'multiply',
                        'value' => 0.5,
                        'applies_to' => 'account_training_buildings',
                    ],
                ],
            ],
        ],
        [
            'code' => 'storage-masterplan-small',
            'name' => 'Slight storage masterplan',
            'size' => 'small',
            'scope' => 'village',
            'treasury_level_req' => 10,
            'effect' => [
                'category' => 'storage-masterplan',
                'summary' => 'Unlocks the construction of great warehouses and great granaries in this village.',
                'modifiers' => [
                    [
                        'type' => 'building_unlock',
                        'operation' => 'unlock',
                        'buildings' => ['great_warehouse', 'great_granary'],
                        'applies_to' => 'village',
                    ],
                ],
            ],
        ],
        [
            'code' => 'storage-masterplan-large',
            'name' => 'Great storage masterplan',
            'size' => 'large',
            'scope' => 'account',
            'treasury_level_req' => 20,
            'effect' => [
                'category' => 'storage-masterplan',
                'summary' => 'Unlocks great warehouses and great granaries for every village on this account.',
                'modifiers' => [
                    [
                        'type' => 'building_unlock',
                        'operation' => 'unlock',
                        'buildings' => ['great_warehouse', 'great_granary'],
                        'applies_to' => 'account',
                    ],
                ],
            ],
        ],
        [
            'code' => 'rivals-confusion-small',
            'name' => 'Rivals slight confusion',
            'size' => 'small',
            'scope' => 'village',
            'treasury_level_req' => 10,
            'effect' => [
                'category' => 'rivals-confusion',
                'summary' => 'Cranny capacity in this village is multiplied by 200 and enemy catapults hit random targets.',
                'modifiers' => [
                    [
                        'type' => 'cranny_capacity',
                        'operation' => 'multiply',
                        'value' => 200,
                        'applies_to' => 'village_cranny',
                    ],
                    [
                        'type' => 'catapult_targeting',
                        'operation' => 'randomize',
                        'applies_to' => 'incoming_attacks',
                    ],
                ],
            ],
        ],
        [
            'code' => 'rivals-confusion-large',
            'name' => 'Rivals great confusion',
            'size' => 'large',
            'scope' => 'account',
            'treasury_level_req' => 20,
            'effect' => [
                'category' => 'rivals-confusion',
                'summary' => 'Cranny capacity across the account is multiplied by 100 and catapults strike at random targets.',
                'modifiers' => [
                    [
                        'type' => 'cranny_capacity',
                        'operation' => 'multiply',
                        'value' => 100,
                        'applies_to' => 'account_crannies',
                    ],
                    [
                        'type' => 'catapult_targeting',
                        'operation' => 'randomize',
                        'applies_to' => 'incoming_attacks',
                    ],
                ],
            ],
        ],
        [
            'code' => 'rivals-confusion-unique',
            'name' => 'Rivals unique confusion',
            'size' => 'unique',
            'scope' => 'account',
            'treasury_level_req' => 20,
            'effect' => [
                'category' => 'rivals-confusion',
                'summary' => 'Crannies hold 500× more resources account-wide and catapults can only target randomly.',
                'modifiers' => [
                    [
                        'type' => 'cranny_capacity',
                        'operation' => 'multiply',
                        'value' => 500,
                        'applies_to' => 'account_crannies',
                    ],
                    [
                        'type' => 'catapult_targeting',
                        'operation' => 'randomize',
                        'applies_to' => 'incoming_attacks',
                    ],
                ],
                'notes' => [
                    'Catapults cannot directly target treasuries in affected villages.',
                ],
            ],
        ],
        [
            'code' => 'artifact-of-the-fool-small',
            'name' => 'Artifact of the slight fool',
            'size' => 'small',
            'scope' => 'village',
            'treasury_level_req' => 10,
            'effect' => [
                'category' => 'artifact-of-the-fool',
                'summary' => 'Applies a random positive or negative artifact effect that rotates every 24 hours.',
                'modifiers' => [
                    [
                        'type' => 'random_effect',
                        'operation' => 'dynamic',
                        'applies_to' => 'village_or_account',
                    ],
                ],
                'notes' => [
                    'May switch between village-only and account-wide scope each rotation.',
                    'Negative modifiers are possible on this variant.',
                ],
            ],
        ],
        [
            'code' => 'artifact-of-the-fool-unique',
            'name' => 'Artifact of the unique fool',
            'size' => 'unique',
            'scope' => 'account',
            'treasury_level_req' => 20,
            'effect' => [
                'category' => 'artifact-of-the-fool',
                'summary' => 'Rotating artifact that only grants positive random effects every 24 hours.',
                'modifiers' => [
                    [
                        'type' => 'random_effect',
                        'operation' => 'dynamic',
                        'applies_to' => 'account_or_village',
                    ],
                ],
                'notes' => [
                    'Scope may alternate between village and account with each rotation.',
                    'Effect magnitude varies but is never detrimental.',
                ],
            ],
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $timestamp = Carbon::now()->toDateTimeString();

        $records = array_map(
            static function (array $definition) use ($timestamp): array {
                $effect = $definition['effect'] + [
                    'size' => $definition['size'],
                    'scope' => $definition['scope'],
                ];

                return [
                    'code' => $definition['code'],
                    'name' => $definition['name'],
                    'size' => $definition['size'],
                    'scope' => $definition['scope'],
                    'treasury_level_req' => $definition['treasury_level_req'],
                    'effect' => json_encode($effect, JSON_THROW_ON_ERROR),
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            },
            self::ARTIFACTS,
        );

        DB::table('artifacts')->upsert(
            $records,
            ['code'],
            ['name', 'size', 'scope', 'treasury_level_req', 'effect', 'updated_at'],
        );
    }
}
