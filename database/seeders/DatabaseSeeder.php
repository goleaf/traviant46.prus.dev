<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::factory()->create([
            'legacy_uid' => User::LEGACY_ADMIN_UID,
            'username' => 'admin',
            'name' => 'Administrator',
            'email' => 'admin@example.com',
            'password' => Hash::make('Admin!234'),
        ]);

        $multihunter = User::factory()->create([
            'legacy_uid' => User::LEGACY_MULTIHUNTER_UID,
            'username' => 'multihunter',
            'name' => 'Multihunter',
            'email' => 'multihunter@example.com',
            'password' => Hash::make('Multi!234'),
        ]);

        User::factory(8)->create();

        $nextLegacyUid = ((int) User::query()->max('legacy_uid')) + 1;

        while (User::isReservedLegacyUid($nextLegacyUid)) {
            $nextLegacyUid++;
        }

        $player = User::factory()->create([
            'legacy_uid' => $nextLegacyUid,
            'username' => 'playerone',
            'name' => 'Player One',
            'email' => 'player@example.com',
        ]);

        $player->sitters()->attach($admin->id);
        $player->sitters()->attach($multihunter->id);
    }
}
