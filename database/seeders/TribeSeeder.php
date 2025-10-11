<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TribeSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('tribes')) {
            if (isset($this->command)) {
                $this->command->warn('Skipping tribes seeding because the table does not exist.');
            }
            return;
        }

        $data = require __DIR__ . '/data/tribes.php';
        $columns = Schema::getColumnListing('tribes');
        $now = Carbon::now();
        $records = [];

        foreach ($data as $tribe) {
            if (!is_array($tribe) || empty($tribe['id']) || empty($tribe['name'])) {
                continue;
            }

            $record = [
                'id' => (int) $tribe['id'],
                'name' => (string) $tribe['name'],
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (in_array('slug', $columns, true)) {
                $record['slug'] = $tribe['slug'] ?? null;
            }

            if (in_array('legacy_id', $columns, true)) {
                $record['legacy_id'] = (int) $tribe['id'];
            }

            if (in_array('display_name', $columns, true)) {
                $record['display_name'] = $tribe['name'];
            }

            if (in_array('bonuses', $columns, true)) {
                $record['bonuses'] = json_encode($tribe['bonuses'] ?? []);
            }

            $records[] = $record;
        }

        if (empty($records)) {
            return;
        }

        DB::table('tribes')->truncate();
        DB::table('tribes')->insert($records);
    }
}
