<?php

declare(strict_types=1);

use App\Livewire\Game\Reports;
use App\Models\Game\Report;
use App\Models\Game\World;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Livewire\Livewire;

use function Pest\Laravel\artisan;

beforeEach(function (): void {
    artisan('migrate:fresh', [
        '--path' => [
            'database/migrations/0001_01_01_000000_create_users_table.php',
            'database/migrations/2025_10_09_174049_add_two_factor_columns_to_users_table.php',
            'database/migrations/2025_10_10_000000_add_ban_columns_to_users_table.php',
            'database/migrations/2025_10_15_000200_add_role_to_users_table.php',
            'database/migrations/2025_10_26_212536_create_alliances_table.php',
            'database/migrations/2025_10_26_212625_add_current_alliance_id_to_users_table.php',
            'database/migrations/2025_10_26_225936_add_beginner_protection_until_to_users_table.php',
            'database/migrations/2025_10_26_230625_add_sitter_permission_matrix_to_users_table.php',
            'database/migrations/2025_10_26_230900_add_race_column_to_users_table.php',
            'database/migrations/2025_10_20_000000_create_worlds_table.php',
            'database/migrations/2025_10_26_212645_create_reports_table.php',
        ],
        '--force' => true,
    ]);
});

it('filters reports by kind for the signed-in player', function (): void {
    $user = User::factory()->create();
    $world = World::factory()->create();

    $combatReport = Report::factory()
        ->for($world)
        ->state([
            'for_user_id' => $user->getKey(),
            'kind' => 'combat',
            'data' => [
                'casualties' => ['infantry' => 18],
                'bounty' => ['wood' => 120],
                'damages' => ['wall' => 2],
            ],
            'created_at' => now()->subMinutes(5),
        ])
        ->create();

    Report::factory()
        ->for($world)
        ->state([
            'for_user_id' => $user->getKey(),
            'kind' => 'trade',
            'data' => [
                'casualties' => ['traders' => 0],
                'bounty' => ['wood' => 0, 'clay' => 200],
                'damages' => ['marketplace' => 0],
            ],
            'created_at' => now()->subMinutes(3),
        ])
        ->create();

    Report::factory()->create(); // Unrelated report for another user.

    $component = Livewire::actingAs($user)->test(Reports::class);

    $component
        ->assertSee('Combat', false)
        ->assertSee('Trade', false)
        ->assertSee('Infantry: 18', false)
        ->assertDontSee('Marketplace: 0', false); // not selected yet

    $component
        ->set('kind', 'trade')
        ->assertSee('Trade', false)
        ->assertDontSee('Infantry: 18', false);

    $component
        ->set('kind', 'all')
        ->call('selectReport', $combatReport->getKey())
        ->assertSet('selectedReportId', $combatReport->getKey())
        ->assertSee('Infantry: 18', false)
        ->assertSee('Wood: 120', false)
        ->assertSee('Wall: 2', false);
});

it('clears the selection when the page changes', function (): void {
    $user = User::factory()->create();
    $world = World::factory()->create();

    $reports = Report::factory()
        ->count(12)
        ->for($world)
        ->sequence(
            fn (Sequence $sequence) => [
                'for_user_id' => $user->getKey(),
                'kind' => $sequence->index % 2 === 0 ? 'combat' : 'system',
                'data' => [
                    'casualties' => ['infantry' => $sequence->index],
                    'bounty' => ['wood' => $sequence->index * 10],
                    'damages' => ['wall' => $sequence->index % 3],
                ],
                'created_at' => now()->subMinutes($sequence->index),
            ],
        )
        ->create();

    $firstReport = $reports->first();

    $component = Livewire::actingAs($user)->test(Reports::class);

    $component
        ->call('selectReport', $firstReport->getKey())
        ->assertSet('selectedReportId', $firstReport->getKey());

    $component
        ->set('page', 2)
        ->assertSet('selectedReportId', null);
});
