<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\MultiAccountAlertSeverity;
use App\Enums\MultiAccountAlertStatus;
use App\Enums\SitterPermission;
use App\Enums\StaffRole;
use App\Models\CampaignCustomerSegment;
use App\Models\LoginActivity;
use App\Models\MultiAccountAlert;
use App\Models\SitterDelegation;
use App\Models\User;
use App\ValueObjects\SitterPermissionSet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CoreUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::factory()->create([
            'legacy_uid' => User::LEGACY_ADMIN_UID,
            'username' => 'admin',
            'name' => 'Administrator',
            'email' => 'admin@example.com',
            'role' => StaffRole::Admin->value,
            'password' => Hash::make('Admin!234'),
            'email_verified_at' => Carbon::now(),
        ]);

        $multihunter = User::factory()->create([
            'legacy_uid' => User::LEGACY_MULTIHUNTER_UID,
            'username' => 'multihunter',
            'name' => 'Multihunter',
            'email' => 'multihunter@example.com',
            'role' => StaffRole::Viewer->value,
            'password' => Hash::make('Multi!234'),
            'email_verified_at' => Carbon::now(),
        ]);

        $player = User::factory()->create([
            'legacy_uid' => 3,
            'username' => 'playerone',
            'name' => 'Player One',
            'email' => 'player@example.com',
            'password' => Hash::make('Player!234'),
            'email_verified_at' => Carbon::now(),
        ]);

        $additionalPlayers = User::factory(8)->create();
        $secondary = $additionalPlayers->first();

        $this->seedSitterDelegations($player, $admin, $multihunter);
        $this->seedLoginActivity($player, $admin, $multihunter, $secondary);
        $this->seedMultiAccountAlerts($player, $secondary);
        $this->seedCustomerSegments($player);
    }

    private function seedSitterDelegations(User $player, User $admin, User $multihunter): void
    {
        $permissions = SitterPermissionSet::fromArray([
            SitterPermission::Build,
            SitterPermission::SendTroops,
            SitterPermission::Trade,
        ]);

        SitterDelegation::factory()->create([
            'owner_user_id' => $player->id,
            'sitter_user_id' => $admin->id,
            'permissions' => $permissions,
            'created_by' => $player->id,
        ]);

        SitterDelegation::factory()->create([
            'owner_user_id' => $player->id,
            'sitter_user_id' => $multihunter->id,
            'permissions' => SitterPermissionSet::fromArray([
                SitterPermission::Farm,
                SitterPermission::ManageMessages,
                SitterPermission::SendResources,
            ]),
            'created_by' => $player->id,
        ]);
    }

    private function seedLoginActivity(User $player, User $admin, User $multihunter, User $secondary): void
    {
        $sharedIp = '198.51.100.24';
        $sharedHash = hash('sha256', $sharedIp);

        LoginActivity::factory()->create([
            'user_id' => $player->id,
            'ip_address' => $sharedIp,
            'ip_address_hash' => $sharedHash,
            'logged_at' => Carbon::now()->subMinutes(10),
        ]);

        $adminIp = '203.0.113.'.fake()->numberBetween(10, 200);

        LoginActivity::factory()->create([
            'user_id' => $admin->id,
            'ip_address' => $adminIp,
            'ip_address_hash' => hash('sha256', $adminIp),
            'logged_at' => Carbon::now()->subMinutes(25),
        ]);

        $sitterIp = '192.0.2.'.fake()->numberBetween(2, 240);

        LoginActivity::factory()->create([
            'user_id' => $player->id,
            'acting_sitter_id' => $admin->id,
            'via_sitter' => true,
            'ip_address' => $sitterIp,
            'ip_address_hash' => hash('sha256', $sitterIp),
            'logged_at' => Carbon::now()->subMinutes(5),
        ]);

        LoginActivity::factory()->create([
            'user_id' => $secondary->id,
            'ip_address' => $sharedIp,
            'ip_address_hash' => $sharedHash,
            'logged_at' => Carbon::now()->subMinutes(15),
        ]);

        $multihunterIp = '198.51.100.'.fake()->numberBetween(50, 200);

        LoginActivity::factory()->create([
            'user_id' => $multihunter->id,
            'ip_address' => $multihunterIp,
            'ip_address_hash' => hash('sha256', $multihunterIp),
            'logged_at' => Carbon::now()->subMinutes(30),
        ]);
    }

    private function seedMultiAccountAlerts(User $player, User $secondary): void
    {
        $ip = '198.51.100.24';
        $hash = hash('sha256', $ip);

        MultiAccountAlert::factory()->create([
            'user_ids' => [$player->id, $secondary->id],
            'ip_address' => $ip,
            'ip_address_hash' => $hash,
            'device_hash' => hash('sha256', Str::uuid()->toString()),
            'severity' => MultiAccountAlertSeverity::High,
            'status' => MultiAccountAlertStatus::Open,
            'occurrences' => 4,
            'metadata' => [
                'notes' => 'Detected shared IP usage between player accounts.',
            ],
            'first_seen_at' => Carbon::now()->subHours(6),
            'window_started_at' => Carbon::now()->subHours(7),
            'last_seen_at' => Carbon::now()->subMinutes(20),
        ]);
    }

    private function seedCustomerSegments(User $player): void
    {
        $activeFilter = [
            ['field' => 'role', 'operator' => 'equals', 'value' => StaffRole::Player->value],
            ['field' => 'email_verified_at', 'operator' => 'is_not_null'],
        ];

        $segments = collect([
            CampaignCustomerSegment::factory()->create([
                'name' => 'Active Players',
                'description' => 'Players with verified emails ready for campaigns.',
                'filters' => $activeFilter,
            ]),
            CampaignCustomerSegment::factory()->create([
                'name' => 'Recent Sitters',
                'description' => 'Accounts that have an active sitter delegation.',
                'filters' => [
                    ['field' => 'id', 'operator' => 'equals', 'value' => $player->id],
                ],
            ]),
        ]);

        $segments->each->recalculateMatchCount();
    }
}
