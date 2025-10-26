<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\SitterDelegation;
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

        $this->ensureDelegation($player->id, $admin->id, $player->id);
        $this->ensureDelegation($player->id, $multihunter->id, $player->id);
    }

    protected function ensureDelegation(int $ownerId, int $sitterId, int $actorId): void
    {
        $delegation = SitterDelegation::query()->firstOrNew([
            'owner_user_id' => $ownerId,
            'sitter_user_id' => $sitterId,
        ]);

        if (! $delegation->exists) {
            $delegation->created_by = $actorId;
            $delegation->permissions = ['full_access'];
        }

        $delegation->updated_by = $actorId;
        $delegation->save();
    }
}
