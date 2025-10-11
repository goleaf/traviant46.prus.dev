<?php

namespace Database\Seeders;

use App\Enums\BuildingType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BuildingTypesSeeder extends Seeder
{
    public function run(): void
    {
        $data = require __DIR__ . '/data/building_types.php';
        $now = Carbon::now();
        $records = [];

        foreach ($data as $entry) {
            if (!is_array($entry) || empty($entry['gid'])) {
                continue;
            }

            $buildingType = BuildingType::tryFrom((int) $entry['gid']);

            if ($buildingType === null) {
                continue;
            }

            $requirements = [];
            if (!empty($entry['req'])) {
                $requirements['req'] = $entry['req'];
            }
            if (!empty($entry['breq'])) {
                $requirements['breq'] = $entry['breq'];
            }

            $records[] = [
                'gid' => $buildingType->value,
                'name' => $buildingType->label(),
                'type' => isset($entry['type']) ? (int) $entry['type'] : null,
                'max_level' => isset($entry['maxLvl']) ? (int) $entry['maxLvl'] : null,
                'extra' => isset($entry['extra']) ? (int) $entry['extra'] : null,
                'crop_consumption' => isset($entry['cu']) ? (int) $entry['cu'] : null,
                'culture_points' => isset($entry['cp']) ? (int) $entry['cp'] : null,
                'growth_factor' => isset($entry['k']) ? (float) $entry['k'] : null,
                'cost' => !empty($entry['cost']) ? json_encode($entry['cost']) : json_encode([]),
                'time' => !empty($entry['time']) ? json_encode($entry['time']) : json_encode([]),
                'requirements' => !empty($requirements) ? json_encode($requirements) : json_encode([]),
                'building_requirements' => !empty($entry['breq']) ? json_encode($entry['breq']) : json_encode([]),
                'capital_only' => !empty($entry['req']['capital']) && (int) $entry['req']['capital'] > 0,
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
