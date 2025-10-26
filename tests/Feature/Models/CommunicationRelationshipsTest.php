<?php

declare(strict_types=1);

use App\Models\Game\CapturedUnit;
use App\Models\Game\MovementOrder;
use App\Models\Game\ReinforcementGarrison;
use App\Models\Game\Village;
use App\Models\Game\VillageResource;
use App\Models\Message;
use App\Models\MessageRecipient;
use App\Models\Report;
use App\Models\ReportRecipient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

it('links messages to recipients and exposes scopes', function (): void {
    $sender = User::factory()->create(['legacy_uid' => 101]);
    $recipient = User::factory()->create(['legacy_uid' => 202]);

    $message = Message::query()->create([
        'legacy_message_id' => 5001,
        'sender_id' => $sender->getKey(),
        'subject' => 'Alliance plans',
        'body' => 'Reinforce north border tonight.',
        'message_type' => 'player',
        'delivery_scope' => 'individual',
        'sent_at' => Carbon::now(),
        'metadata' => ['auto_type' => 0],
    ]);

    MessageRecipient::query()->create([
        'message_id' => $message->getKey(),
        'recipient_id' => $recipient->getKey(),
        'status' => 'unread',
        'is_archived' => false,
        'is_muted' => false,
        'is_reported' => false,
        'flags' => ['auto_type' => 0],
    ]);

    expect($message->recipients)->toHaveCount(1);
    expect(Message::query()->forRecipient($recipient)->exists())->toBeTrue();
    expect(MessageRecipient::query()->unread()->visible()->count())->toBe(1);
});

it('associates reports with recipients and supports query scopes', function (): void {
    $owner = User::factory()->create(['legacy_uid' => 303]);
    $triggered = Carbon::now()->subMinute();

    $report = Report::query()->create([
        'legacy_report_id' => 7001,
        'user_id' => $owner->getKey(),
        'report_type' => 'attack',
        'payload' => ['result' => 'victory'],
        'triggered_at' => $triggered,
    ]);

    ReportRecipient::query()->create([
        'report_id' => $report->getKey(),
        'recipient_id' => $owner->getKey(),
        'status' => 'unread',
        'is_flagged' => false,
    ]);

    expect(Report::query()->forRecipient($owner)->exists())->toBeTrue();
    expect(ReportRecipient::query()->unread()->visible()->count())->toBe(1);
    expect(Report::query()->triggeredBetween($triggered->copy()->subMinute(), now())->count())->toBe(1);
});

it('hydrates village relationships for combat entities', function (): void {
    $owner = User::factory()->create(['legacy_uid' => 404]);

    $village = Village::factory()->create([
        'user_id' => $owner->getKey(),
        'legacy_kid' => 123456,
    ]);

    VillageResource::query()->create([
        'village_id' => $village->getKey(),
        'resource_type' => 'wood',
        'level' => 5,
        'production_per_hour' => 80,
        'storage_capacity' => 4000,
        'bonuses' => ['oasis' => 25],
    ]);

    MovementOrder::query()->create([
        'legacy_movement_id' => 8001,
        'user_id' => $owner->getKey(),
        'origin_village_id' => $village->getKey(),
        'target_village_id' => $village->getKey(),
        'movement_type' => 'attack',
        'status' => 'pending',
        'depart_at' => Carbon::now(),
        'arrive_at' => Carbon::now()->addMinutes(30),
        'payload' => ['units' => ['u1' => 100]],
        'metadata' => ['mode' => 0],
    ]);

    ReinforcementGarrison::query()->create([
        'legacy_enforcement_id' => 9001,
        'owner_user_id' => $owner->getKey(),
        'home_village_id' => $village->getKey(),
        'stationed_village_id' => $village->getKey(),
        'unit_composition' => ['u3' => 30],
        'upkeep' => 60,
        'is_active' => true,
        'deployed_at' => Carbon::now(),
        'metadata' => ['race' => 1],
    ]);

    CapturedUnit::query()->create([
        'legacy_trapped_id' => 9101,
        'captor_village_id' => $village->getKey(),
        'unit_composition' => ['u5' => 12],
        'status' => 'captured',
        'captured_at' => Carbon::now(),
    ]);

    expect($village->resources)->toHaveCount(1);
    expect($village->movements)->toHaveCount(1);
    expect($village->incomingMovements)->toHaveCount(1);
    expect($village->stationedReinforcements)->toHaveCount(1);
    expect($village->capturedUnits)->toHaveCount(1);

    expect(MovementOrder::query()->pending()->forUser($owner)->count())->toBe(1);
    expect(Village::query()->forLegacyKid(123456)->first())->toBeInstanceOf(Village::class);
});
