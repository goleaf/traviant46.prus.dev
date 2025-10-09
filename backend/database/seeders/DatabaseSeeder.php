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
            'legacy_uid' => 0,
            'username' => 'admin',
            'name' => 'Administrator',
            'email' => 'admin@example.com',
            'password' => Hash::make('Admin!234'),
        ]);

        $multihunter = User::factory()->create([
            'legacy_uid' => 2,
            'username' => 'multihunter',
            'name' => 'Multihunter',
            'email' => 'multihunter@example.com',
            'password' => Hash::make('Multi!234'),
        ]);

        User::factory(8)->create();

        $player = User::factory()->create([
            'legacy_uid' => (int) User::query()->max('legacy_uid') + 1,
            'username' => 'playerone',
            'name' => 'Player One',
            'email' => 'player@example.com',
        ]);

        $player->sitters()->attach($admin->id);
        $player->sitters()->attach($multihunter->id);

        $this->call([
            GameplayMetadataSeeder::class,
        ]);
    }
}
