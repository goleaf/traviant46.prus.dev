<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BuildingTypesSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('building_types')) {
            if (isset($this->command)) {
                $this->command->warn('Skipping building_types seeding because the table does not exist.');
            }
            return;
        }

        $data = require __DIR__ . '/data/building_types.php';
        $now = Carbon::now();
        $records = [];

        foreach ($data as $entry) {
            if (!is_array($entry) || empty($entry['gid']) || empty($entry['name'])) {
                continue;
            }

            $cost = $entry['cost'] ?? [];
            $time = $entry['time'] ?? [];
            $requirements = $entry['req'] ?? [];
            $buildingRequirements = $entry['breq'] ?? [];

            $attributes = array_diff_key($entry, array_flip([
                'gid',
                'name',
                'type',
                'maxLvl',
                'extra',
                'cu',
                'cp',
                'k',
                'cost',
                'time',
                'req',
                'breq',
            ]));

            $records[] = [
                'gid' => (int) $entry['gid'],
                'name' => (string) $entry['name'],
                'type' => isset($entry['type']) ? (int) $entry['type'] : null,
                'max_level' => isset($entry['maxLvl']) ? (int) $entry['maxLvl'] : null,
                'extra' => isset($entry['extra']) ? (int) $entry['extra'] : null,
                'crop_consumption' => isset($entry['cu']) ? (int) $entry['cu'] : null,
                'culture_points' => isset($entry['cp']) ? (int) $entry['cp'] : null,
                'growth_factor' => isset($entry['k']) ? (float) $entry['k'] : null,
                'cost' => !empty($cost) ? json_encode($cost) : json_encode([]),
                'time' => !empty($time) ? json_encode($time) : json_encode([]),
                'requirements' => !empty($requirements) ? json_encode($requirements) : json_encode([]),
                'building_requirements' => !empty($buildingRequirements) ? json_encode($buildingRequirements) : json_encode([]),
                'capital_only' => !empty($entry['req']['capital']) && (int) $entry['req']['capital'] > 0,
                'attributes' => !empty($attributes) ? json_encode($attributes) : json_encode([]),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('building_types')->truncate();

        if (!empty($records)) {
            DB::table('building_types')->insert($records);
        }
    }
}
