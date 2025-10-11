<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ResearchTreeSeeder extends Seeder
{
    private const TARGET_TABLE = 'unit_research_requirements';

    public function run(): void
    {
        if (!Schema::hasTable(self::TARGET_TABLE)) {
            if (isset($this->command)) {
                $this->command->warn('Skipping research tree seeding because the unit_research_requirements table does not exist.');
            }
            return;
        }

        $data = require __DIR__ . '/data/unit_types.php';
        $columns = Schema::getColumnListing(self::TARGET_TABLE);
        $now = Carbon::now();
        $records = [];

        foreach ($data as $tribe) {
            if (!is_array($tribe) || empty($tribe['tribe_id']) || empty($tribe['units'])) {
                continue;
            }

            foreach ($tribe['units'] as $unit) {
                if (empty($unit['requirements']) || !is_array($unit['requirements'])) {
                    continue;
                }

                foreach ($unit['requirements'] as $buildingGid => $level) {
                    $row = [
                        'tribe_id' => (int) $tribe['tribe_id'],
                        'unit_slot' => (int) $unit['slot'],
                        'building_gid' => (int) $buildingGid,
                        'required_level' => (int) $level,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    if (in_array('unit_legacy_id', $columns, true)) {
                        $row['unit_legacy_id'] = $unit['legacy_id'] ?? null;
                    }

                    if (in_array('unit_code', $columns, true)) {
                        $row['unit_code'] = $unit['code'] ?? null;
                    }

                    $records[] = $row;
                }
            }
        }

        if (empty($records)) {
            return;
        }

        DB::table(self::TARGET_TABLE)->truncate();
        DB::table(self::TARGET_TABLE)->insert($records);
    }
}
