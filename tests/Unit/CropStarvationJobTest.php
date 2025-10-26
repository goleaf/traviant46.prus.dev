<?php

declare(strict_types=1);

use App\Actions\Game\ApplyStarvationAction;
use App\Jobs\CropStarvationJob;
use App\Models\Game\Village;
use App\Models\User;
use App\Notifications\Game\VillageStarvationNotification;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    Schema::dropAllTables();

    Schema::create('users', function (Blueprint $table): void {
        $table->id();
        $table->unsignedInteger('legacy_uid')->nullable()->unique();
        $table->string('username')->unique();
        $table->string('name');
        $table->string('email')->unique();
        $table->timestamp('email_verified_at')->nullable();
        $table->string('password');
        $table->string('role')->default('player');
        $table->unsignedTinyInteger('race')->nullable();
        $table->unsignedTinyInteger('tribe')->nullable();
        $table->rememberToken();
        $table->boolean('is_banned')->default(false);
        $table->string('ban_reason')->nullable();
        $table->timestamp('ban_issued_at')->nullable();
        $table->timestamp('ban_expires_at')->nullable();
        $table->timestamp('beginner_protection_until')->nullable();
        $table->timestamps();
    });

    Schema::create('villages', function (Blueprint $table): void {
        $table->id();
        $table->unsignedInteger('legacy_kid')->nullable()->unique();
        $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
        $table->foreignId('watcher_user_id')->nullable()->constrained('users')->nullOnDelete();
        $table->unsignedBigInteger('alliance_id')->nullable();
        $table->string('name')->nullable();
        $table->integer('x_coordinate')->default(0);
        $table->integer('y_coordinate')->default(0);
        $table->unsignedTinyInteger('terrain_type')->default(1);
        $table->string('village_category', 32)->nullable();
        $table->boolean('is_capital')->default(false);
        $table->boolean('is_wonder_village')->default(false);
        $table->integer('population')->default(0);
        $table->unsignedTinyInteger('loyalty')->default(100);
        $table->unsignedInteger('culture_points')->default(0);
        $table->json('resource_balances')->nullable();
        $table->json('storage')->nullable();
        $table->json('production')->nullable();
        $table->json('defense_bonus')->nullable();
        $table->timestamp('founded_at')->nullable();
        $table->timestamp('abandoned_at')->nullable();
        $table->timestamp('last_loyalty_change_at')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('notifications', function (Blueprint $table): void {
        $table->uuid('id')->primary();
        $table->string('type');
        $table->morphs('notifiable');
        $table->text('data');
        $table->timestamp('read_at')->nullable();
        $table->timestamps();
    });
});

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

    $job = new CropStarvationJob;
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
