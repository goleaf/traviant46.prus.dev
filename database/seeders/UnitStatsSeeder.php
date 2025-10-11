<?php

namespace Database\Seeders;

use App\Enums\UnitType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UnitStatsSeeder extends Seeder
{
    public function run(): void
    {
        $data = require __DIR__ . '/data/unit_stats.php';
        $now = Carbon::now();
        $records = [];

        foreach ($data as $raceIndex => $units) {
            if (!is_array($units)) {
                continue;
            }

            foreach ($units as $slotIndex => $unit) {
                if (!is_array($unit)) {
                    continue;
                }

                $race = isset($unit['race']) ? (int) $unit['race'] : (int) $raceIndex;
                $unitType = UnitType::fromRaceAndSlot($race, (int) $slotIndex);

                if ($unitType === null) {
                    continue;
                }

                $cost = $unit['cost'] ?? [];
                $buildingRequirements = $unit['breq'] ?? [];
                $legacyCode = $unit['type'] ?? null;

                $attributes = $unit;
                foreach ([
                    'off',
                    'def_i',
                    'def_c',
                    'speed',
                    'cap',
                    'cu',
                    'time',
                    'rs_time',
                    'mask',
                    'cost',
                    'breq',
                    'race',
                    'type',
                ] as $key) {
                    unset($attributes[$key]);
                }

                if ($legacyCode !== null) {
                    $attributes['legacy_unit_code'] = $legacyCode;
                }

                $attributes['unit_type'] = $unitType->value;
                $attributes['unit_name'] = $unitType->label();
                $attributes['unit_slug'] = $unitType->slug();

                $records[] = [
                    'race' => $race,
                    'slot' => (int) $slotIndex,
                    'unit_code' => $legacyCode !== null ? (string) $legacyCode : null,
                    'attack' => isset($unit['off']) ? (int) $unit['off'] : 0,
                    'defense_infantry' => isset($unit['def_i']) ? (int) $unit['def_i'] : 0,
                    'defense_cavalry' => isset($unit['def_c']) ? (int) $unit['def_c'] : 0,
                    'speed' => isset($unit['speed']) ? (int) $unit['speed'] : 0,
                    'capacity' => isset($unit['cap']) ? (int) $unit['cap'] : 0,
                    'crop_consumption' => isset($unit['cu']) ? (int) $unit['cu'] : 0,
                    'training_time' => isset($unit['time']) ? (int) $unit['time'] : 0,
                    'research_time' => isset($unit['rs_time']) ? (int) $unit['rs_time'] : null,
                    'mask' => isset($unit['mask']) ? (int) $unit['mask'] : null,
                    'cost' => !empty($cost) ? json_encode($cost) : json_encode([]),
                    'building_requirements' => !empty($buildingRequirements) ? json_encode($buildingRequirements) : json_encode([]),
                    'attributes' => !empty($attributes) ? json_encode($attributes) : json_encode([]),
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
