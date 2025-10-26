<?php

use App\Actions\Game\ApplyStarvationAction;
use App\Jobs\CropStarvationJob;
use App\Models\Game\Village;
use App\Models\User;
use App\Notifications\Game\VillageStarvationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

it('applies starvation and notifies eligible villages', function (): void {
    Carbon::setTestNow($now = Carbon::parse('2025-05-15 12:00:00'));

    Notification::fake();

    $owner = User::factory()->create();
    $watcher = User::factory()->create();

    $dueVillage = Village::factory()->create([
        'user_id' => $owner->id,
        'watcher_user_id' => $watcher->id,
        'resource_balances' => [
            'wood' => 0,
            'clay' => 0,
            'iron' => 0,
            'crop' => -150,
        ],
        'storage' => [
            'granary' => 8_000,
            'granary_empty_eta' => $now->copy()->subMinutes(10)->toIso8601String(),
        ],
    ]);

    Village::factory()->create([
        'user_id' => $owner->id,
        'resource_balances' => [
            'wood' => 0,
            'clay' => 0,
            'iron' => 0,
            'crop' => -50,
        ],
        'storage' => [
            'granary' => 8_000,
            'granary_empty_eta' => $now->copy()->addMinutes(10)->toIso8601String(),
        ],
    ]);

    Village::factory()->create([
        'user_id' => $owner->id,
        'resource_balances' => [
            'wood' => 0,
            'clay' => 0,
            'iron' => 0,
            'crop' => 200,
        ],
        'storage' => [
            'granary' => 8_000,
            'granary_empty_eta' => $now->copy()->subMinutes(5)->toIso8601String(),
        ],
    ]);

    $action = \Mockery::mock(ApplyStarvationAction::class);
    $action
        ->shouldReceive('execute')
        ->once()
        ->with(\Mockery::on(static fn (Village $candidate): bool => $candidate->is($dueVillage)))
        ->andReturnNull();

    $job = new CropStarvationJob();
    $job->handle($action);

    Notification::assertSentTo(
        [$owner, $watcher],
        VillageStarvationNotification::class,
        static function (VillageStarvationNotification $notification) use ($dueVillage): bool {
            return $notification->villageId === $dueVillage->getKey();
        },
    );

    Notification::assertSentToTimes($owner, VillageStarvationNotification::class, 1);
    Notification::assertSentToTimes($watcher, VillageStarvationNotification::class, 1);

    Carbon::setTestNow();
});
