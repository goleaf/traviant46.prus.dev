<?php

declare(strict_types=1);

use App\Livewire\Game\QuestLog;
use App\Models\Game\Quest;
use App\Models\Game\QuestProgress;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function defineQuestConfig(): void
{
    Config::set('quests.definitions', [
        'tutorial_welcome' => [
            'objectives' => [
                ['key' => 'build_main', 'label' => 'Build your Main Building', 'description' => 'Upgrade it to level 1.'],
            ],
            'rewards' => [
                ['key' => 'starter_pack', 'label' => 'Starter resources', 'details' => 'Wood, clay, iron, and crop +200 each.'],
            ],
        ],
        'daily_trade' => [
            'objectives' => [
                ['key' => 'send_merchants', 'label' => 'Dispatch merchants', 'description' => 'Send 3 successful trade routes.'],
            ],
            'rewards' => [
                ['key' => 'silver_bonus', 'label' => 'Silver bonus', 'details' => 'Receive 50 silver for auctions.'],
            ],
        ],
    ]);
}

it('renders tutorial and daily quests with objectives and rewards', function (): void {
    defineQuestConfig();

    $user = User::factory()->create();

    Quest::factory()->create([
        'quest_code' => 'tutorial_welcome',
        'title' => 'Welcome to Travian',
        'description' => 'Follow the tutorial questline.',
        'is_repeatable' => false,
    ]);

    Quest::factory()->create([
        'quest_code' => 'daily_trade',
        'title' => 'Trade Supplier',
        'description' => 'Keep merchants active every day.',
        'is_repeatable' => true,
    ]);

    $this->actingAs($user);

    Livewire::test(QuestLog::class)
        ->assertSee('Tutorial quests')
        ->assertSee('Daily quests')
        ->assertSee('Build your Main Building')
        ->assertSee('Dispatch merchants')
        ->assertSet('tutorialQuests.0.title', 'Welcome to Travian')
        ->assertSet('dailyQuests.0.state', QuestProgress::STATE_PENDING);
});

it('marks a quest as complete and persists progress state', function (): void {
    defineQuestConfig();

    $now = Carbon::parse('2025-01-01 12:00:00');
    Carbon::setTestNow($now);

    $user = User::factory()->create();

    $quest = Quest::factory()->create([
        'quest_code' => 'tutorial_welcome',
        'title' => 'Welcome to Travian',
        'description' => 'Follow the tutorial questline.',
        'is_repeatable' => false,
    ]);

    $this->actingAs($user);

    Livewire::test(QuestLog::class)
        ->call('completeQuest', $quest->getKey())
        ->assertSet('tutorialQuests.0.state', QuestProgress::STATE_COMPLETED)
        ->assertSet('statusMessage', __('Quest marked as complete. Rewards are now ready to claim.'));

    $progress = QuestProgress::query()
        ->where('quest_id', $quest->getKey())
        ->where('user_id', $user->getKey())
        ->first();

    expect($progress)->not->toBeNull();
    expect($progress->state)->toEqual(QuestProgress::STATE_COMPLETED);

    /** @var array<string, mixed> $payload */
    $payload = $progress->progress;

    expect($payload['completed_at'])->toEqual($now->toIso8601String());

    $objectives = collect($payload['objectives'] ?? []);
    expect($objectives)->not->toBeEmpty();
    expect($objectives->every(fn ($objective) => ! empty($objective['completed'])))->toBeTrue();

    $rewards = collect($payload['rewards'] ?? []);
    expect($rewards)->not->toBeEmpty();
    expect($rewards->every(fn ($reward) => ! empty($reward['claimable'])))->toBeTrue();

    Carbon::setTestNow();
});
