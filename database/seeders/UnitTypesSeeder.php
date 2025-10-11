<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UnitTypesSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('unit_stats')) {
            if (isset($this->command)) {
                $this->command->warn('Skipping unit_stats seeding because the table does not exist.');
            }
            return;
        }

        $data = require __DIR__ . '/data/unit_types.php';
        $now = Carbon::now();
        $records = [];

        foreach ($data as $tribe) {
            if (!is_array($tribe) || empty($tribe['tribe_id']) || empty($tribe['units'])) {
                continue;
            }

            $tribeId = (int) $tribe['tribe_id'];

            foreach ($tribe['units'] as $unit) {
                if (!is_array($unit) || empty($unit['slot'])) {
                    continue;
                }

                $attributes = [
                    'legacy_id' => $unit['legacy_id'] ?? null,
                    'display_name' => $unit['name'] ?? null,
                ];

                if (!empty($unit['attributes']) && is_array($unit['attributes'])) {
                    $attributes = array_merge($attributes, $unit['attributes']);
                }

                if (array_key_exists('mask', $attributes)) {
                    unset($attributes['mask']);
                }

                $records[] = [
                    'race' => $tribeId,
                    'slot' => (int) $unit['slot'],
                    'unit_code' => $unit['code'] ?? null,
                    'attack' => isset($unit['attack']) ? (int) $unit['attack'] : 0,
                    'defense_infantry' => isset($unit['defense_infantry']) ? (int) $unit['defense_infantry'] : 0,
                    'defense_cavalry' => isset($unit['defense_cavalry']) ? (int) $unit['defense_cavalry'] : 0,
                    'speed' => isset($unit['speed']) ? (int) $unit['speed'] : 0,
                    'capacity' => isset($unit['capacity']) ? (int) $unit['capacity'] : 0,
                    'crop_consumption' => isset($unit['crop_consumption']) ? (int) $unit['crop_consumption'] : 0,
                    'training_time' => isset($unit['training_time']) ? (int) $unit['training_time'] : 0,
                    'research_time' => isset($unit['research_time']) && $unit['research_time'] !== null
                        ? (int) $unit['research_time']
                        : null,
                    'mask' => isset($unit['attributes']['mask']) ? (int) $unit['attributes']['mask'] : null,
                    'cost' => !empty($unit['cost']) ? json_encode($unit['cost']) : json_encode([]),
                    'building_requirements' => !empty($unit['requirements']) ? json_encode($unit['requirements']) : json_encode([]),
                    'attributes' => json_encode(array_filter($attributes, static fn ($value) => $value !== null)),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('unit_stats')->truncate();

        if (!empty($records)) {
            DB::table('unit_stats')->insert($records);
        }
    }
}
