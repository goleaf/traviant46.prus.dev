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
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('database.connections.legacy', [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
    ]);

    DB::purge('legacy');
});

it('imports legacy villages with resource snapshots', function (): void {
    createLegacyVillageSchema();

    User::factory()->create(['legacy_uid' => 1]);

    DB::connection('legacy')->table('vdata')->insert([
        'id' => 1,
        'kid' => 1001,
        'owner' => 1,
        'name' => 'Legacy Hamlet',
        'x' => 12,
        'y' => -8,
        'fieldtype' => 3,
        'type' => 0,
        'capital' => 0,
        'wonder' => 0,
        'pop' => 210,
        'loyalty' => 85,
        'cp' => 120,
        'wood' => 1200,
        'clay' => 1100,
        'iron' => 900,
        'crop' => 800,
        'woodp' => 60,
        'clayp' => 55,
        'ironp' => 45,
        'cropp' => 40,
        'maxstore' => 8000,
        'extraMaxstore' => 1000,
        'maxcrop' => 6000,
        'extraMaxcrop' => 0,
        'upkeep' => 50,
        'creation' => Carbon::now()->subDays(2)->timestamp,
        'last_update' => Carbon::now()->subMinute()->timestamp,
    ]);

    $exitCode = Artisan::call('travian:import-villages', ['--chunk' => 50]);

    expect($exitCode)->toBe(0);

    $village = Village::query()->where('legacy_kid', 1001)->first();
    expect($village)->not->toBeNull();
    expect($village->loyalty)->toBe(85);
    expect($village->resource_balances['wood'])->toBe(1200);

    $resource = VillageResource::query()->where('village_id', $village->getKey())->where('resource_type', 'wood')->first();
    expect($resource)->not->toBeNull();
    expect($resource->production_per_hour)->toBe(60);
});

it('imports legacy messages and reconciles recipient state', function (): void {
    createLegacyMessageSchema();

    $sender = User::factory()->create(['legacy_uid' => 10]);
    $recipient = User::factory()->create(['legacy_uid' => 11]);

    DB::connection('legacy')->table('mdata')->insert([
        'id' => 2001,
        'uid' => 10,
        'to_uid' => 11,
        'subject' => 'Welcome',
        'message' => 'Prepare for battle.',
        'viewed' => 0,
        'archived' => 0,
        'delete_receiver' => 0,
        'delete_sender' => 0,
        'reported' => 0,
        'autoType' => 0,
        'isAlliance' => 0,
        'time' => Carbon::now()->subHour()->timestamp,
        'md5_checksum' => md5('Prepare for battle.'),
    ]);

    $exitCode = Artisan::call('travian:import-messages', ['--chunk' => 25]);

    expect($exitCode)->toBe(0);
    expect(Message::query()->count())->toBe(1);
    expect(MessageRecipient::query()->count())->toBe(1);

    $message = Message::query()->first();
    expect($message->sender_id)->toBe($sender->getKey());
    expect($message->recipients)->toHaveCount(1);

    $recipientState = MessageRecipient::query()->first();
    expect($recipientState->recipient_id)->toBe($recipient->getKey());
    expect($recipientState->status)->toBe('unread');
});

it('imports combat movements, garrisons, captured troops, and reports', function (): void {
    createLegacyMovementSchema();
    createLegacyGarrisonSchema();
    createLegacyCapturedSchema();
    createLegacyReportSchema();

    $owner = User::factory()->create(['legacy_uid' => 20]);
    $origin = Village::factory()->create(['legacy_kid' => 3001, 'user_id' => $owner->getKey()]);
    $target = Village::factory()->create(['legacy_kid' => 3002]);

    DB::connection('legacy')->table('movement')->insert([
        'id' => 30001,
        'uid' => 20,
        'kid' => 3001,
        'to_kid' => 3002,
        'mode' => 0,
        'attack_type' => 1,
        'spyType' => 0,
        'ctar1' => 0,
        'ctar2' => 0,
        'redeployHero' => 0,
        'start_time' => Carbon::now()->subMinutes(10)->timestamp,
        'end_time' => Carbon::now()->addMinutes(20)->timestamp,
        'wood' => 0,
        'clay' => 0,
        'iron' => 0,
        'crop' => 0,
        'u1' => 50,
        'u2' => 25,
    ]);

    DB::connection('legacy')->table('enforcement')->insert([
        'id' => 40001,
        'uid' => 20,
        'kid' => 3001,
        'to_kid' => 3002,
        'race' => 1,
        'pop' => 40,
        'u1' => 20,
    ]);

    DB::connection('legacy')->table('trapped')->insert([
        'id' => 50001,
        'uid' => 20,
        'kid' => 3001,
        'to_kid' => 3002,
        'race' => 1,
        'u1' => 5,
    ]);

    DB::connection('legacy')->table('ndata')->insert([
        'id' => 60001,
        'uid' => 20,
        'aid' => null,
        'kid' => 3001,
        'to_kid' => 3002,
        'type' => 1,
        'time' => Carbon::now()->subMinutes(5)->timestamp,
        'data' => json_encode(['outcome' => 'victory']),
        'bounty' => json_encode(['wood' => 100]),
        'viewed' => 0,
        'archive' => 0,
        'delete' => 0,
        'losses' => 12,
    ]);

    $exitCode = Artisan::call('travian:import-combat-state', ['--chunk' => 50]);

    expect($exitCode)->toBe(0);
    expect(MovementOrder::query()->count())->toBe(1);
    expect(ReinforcementGarrison::query()->count())->toBe(1);
    expect(CapturedUnit::query()->count())->toBe(1);
    expect(Report::query()->count())->toBe(1);
    expect(ReportRecipient::query()->count())->toBe(1);

    $movement = MovementOrder::query()->first();
    expect($movement->originVillage->is($origin))->toBeTrue();
    expect($movement->payload['units']['u1'])->toBe(50);

    $reportRecipient = ReportRecipient::query()->first();
    expect($reportRecipient->status)->toBe('unread');
});

function createLegacyVillageSchema(): void
{
    Schema::connection('legacy')->dropIfExists('vdata');
    Schema::connection('legacy')->create('vdata', function (Blueprint $table): void {
        $table->increments('id');
        $table->integer('kid');
        $table->integer('owner')->nullable();
        $table->integer('checker')->nullable();
        $table->string('name')->nullable();
        $table->integer('x')->nullable();
        $table->integer('y')->nullable();
        $table->tinyInteger('fieldtype')->nullable();
        $table->tinyInteger('type')->nullable();
        $table->boolean('capital')->default(false);
        $table->boolean('wonder')->default(false);
        $table->integer('pop')->nullable();
        $table->integer('loyalty')->nullable();
        $table->integer('cp')->nullable();
        $table->integer('wood')->nullable();
        $table->integer('clay')->nullable();
        $table->integer('iron')->nullable();
        $table->integer('crop')->nullable();
        $table->integer('woodp')->nullable();
        $table->integer('clayp')->nullable();
        $table->integer('ironp')->nullable();
        $table->integer('cropp')->nullable();
        $table->integer('maxstore')->nullable();
        $table->integer('extraMaxstore')->nullable();
        $table->integer('maxcrop')->nullable();
        $table->integer('extraMaxcrop')->nullable();
        $table->integer('upkeep')->nullable();
        $table->integer('creation')->nullable();
        $table->integer('last_update')->nullable();
    });
}

function createLegacyMessageSchema(): void
{
    Schema::connection('legacy')->dropIfExists('mdata');
    Schema::connection('legacy')->create('mdata', function (Blueprint $table): void {
        $table->increments('id');
        $table->integer('uid')->nullable();
        $table->integer('to_uid')->nullable();
        $table->integer('autoType')->default(0);
        $table->boolean('isAlliance')->default(false);
        $table->string('subject')->nullable();
        $table->text('message')->nullable();
        $table->boolean('viewed')->default(false);
        $table->boolean('archived')->default(false);
        $table->boolean('delete_receiver')->default(false);
        $table->boolean('delete_sender')->default(false);
        $table->boolean('reported')->default(false);
        $table->integer('time')->nullable();
        $table->string('md5_checksum')->nullable();
    });
}

function createLegacyMovementSchema(): void
{
    Schema::connection('legacy')->dropIfExists('movement');
    Schema::connection('legacy')->create('movement', function (Blueprint $table): void {
        $table->increments('id');
        $table->integer('uid')->nullable();
        $table->integer('kid')->nullable();
        $table->integer('to_kid')->nullable();
        $table->tinyInteger('mode')->default(0);
        $table->tinyInteger('attack_type')->default(0);
        $table->tinyInteger('spyType')->default(0);
        $table->tinyInteger('ctar1')->default(0);
        $table->tinyInteger('ctar2')->default(0);
        $table->boolean('redeployHero')->default(false);
        $table->integer('start_time')->nullable();
        $table->integer('end_time')->nullable();
        $table->integer('return_time')->nullable();
        $table->integer('wood')->nullable();
        $table->integer('clay')->nullable();
        $table->integer('iron')->nullable();
        $table->integer('crop')->nullable();
        foreach (range(1, 11) as $slot) {
            $table->integer('u'.$slot)->default(0);
        }
    });
}

function createLegacyGarrisonSchema(): void
{
    Schema::connection('legacy')->dropIfExists('enforcement');
    Schema::connection('legacy')->create('enforcement', function (Blueprint $table): void {
        $table->increments('id');
        $table->integer('uid')->nullable();
        $table->integer('kid')->nullable();
        $table->integer('to_kid')->nullable();
        $table->tinyInteger('race')->nullable();
        $table->integer('pop')->nullable();
        foreach (range(1, 11) as $slot) {
            $table->integer('u'.$slot)->default(0);
        }
    });
}

function createLegacyCapturedSchema(): void
{
    Schema::connection('legacy')->dropIfExists('trapped');
    Schema::connection('legacy')->create('trapped', function (Blueprint $table): void {
        $table->increments('id');
        $table->integer('uid')->nullable();
        $table->integer('kid')->nullable();
        $table->integer('to_kid')->nullable();
        $table->tinyInteger('race')->nullable();
        foreach (range(1, 11) as $slot) {
            $table->integer('u'.$slot)->default(0);
        }
    });
}

function createLegacyReportSchema(): void
{
    Schema::connection('legacy')->dropIfExists('ndata');
    Schema::connection('legacy')->create('ndata', function (Blueprint $table): void {
        $table->increments('id');
        $table->integer('uid')->nullable();
        $table->integer('aid')->nullable();
        $table->integer('kid')->nullable();
        $table->integer('to_kid')->nullable();
        $table->tinyInteger('type')->nullable();
        $table->tinyInteger('category')->nullable();
        $table->boolean('is_public')->default(false);
        $table->boolean('is_system')->default(false);
        $table->boolean('is_persistent')->default(false);
        $table->integer('losses')->nullable();
        $table->text('data')->nullable();
        $table->text('bounty')->nullable();
        $table->boolean('viewed')->default(false);
        $table->boolean('archive')->default(false);
        $table->boolean('delete')->default(false);
        $table->boolean('forwarded')->default(false);
        $table->string('share_key')->nullable();
        $table->integer('time')->nullable();
    });
}
