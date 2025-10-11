<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GameConfigSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('game_configurations')) {
            if (isset($this->command)) {
                $this->command->warn('Skipping game_configurations seeding because the table does not exist.');
            }
            return;
        }

        $now = Carbon::now();

        $record = [
            'id' => 1,
            'map_size' => 200,
            'is_installed' => false,
            'automation_enabled' => true,
            'fake_account_process_enabled' => true,
            'maintenance_mode' => false,
            'maintenance_delay_seconds' => 0,
            'needs_restart' => false,
            'is_restore' => false,
            'login_info_title' => '',
            'login_info_html' => '',
            'maintenance_message' => '',
            'created_at' => $now,
            'updated_at' => $now,
        ];

        DB::table('game_configurations')->truncate();
        DB::table('game_configurations')->insert($record);
    }
}
