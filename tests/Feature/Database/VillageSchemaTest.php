<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('migrates villages schema with loyalty and resource metadata', function (): void {
    expect(Schema::hasColumns('villages', [
        'legacy_kid',
        'user_id',
        'watcher_user_id',
        'terrain_type',
        'village_category',
        'population',
        'loyalty',
        'culture_points',
        'resource_balances',
        'storage',
        'production',
        'defense_bonus',
        'founded_at',
        'abandoned_at',
        'last_loyalty_change_at',
        'deleted_at',
    ]))->toBeTrue();
});

it('creates resource, communication, and combat support tables', function (): void {
    expect(Schema::hasColumns('village_resources', [
        'village_id',
        'resource_type',
        'level',
        'production_per_hour',
        'storage_capacity',
        'bonuses',
        'last_collected_at',
    ]))->toBeTrue();

    expect(Schema::hasColumns('movement_orders', [
        'legacy_movement_id',
        'origin_village_id',
        'target_village_id',
        'movement_type',
        'mission',
        'status',
        'depart_at',
        'arrive_at',
        'payload',
        'metadata',
        'deleted_at',
    ]))->toBeTrue();

    expect(Schema::hasColumns('messages', [
        'legacy_message_id',
        'sender_id',
        'subject',
        'body',
        'message_type',
        'delivery_scope',
        'sent_at',
        'metadata',
    ]))->toBeTrue();

    expect(Schema::hasColumns('message_recipients', [
        'message_id',
        'recipient_id',
        'status',
        'is_archived',
        'is_muted',
        'read_at',
        'deleted_at',
        'flags',
    ]))->toBeTrue();

    expect(Schema::hasColumns('reports', [
        'legacy_report_id',
        'user_id',
        'origin_village_id',
        'target_village_id',
        'report_type',
        'payload',
        'bounty',
        'triggered_at',
        'metadata',
    ]))->toBeTrue();

    expect(Schema::hasColumns('report_recipients', [
        'report_id',
        'recipient_id',
        'status',
        'viewed_at',
        'deleted_at',
    ]))->toBeTrue();

    expect(Schema::hasColumns('reinforcement_garrisons', [
        'legacy_enforcement_id',
        'owner_user_id',
        'home_village_id',
        'stationed_village_id',
        'unit_composition',
        'deployed_at',
    ]))->toBeTrue();

    expect(Schema::hasColumns('captured_units', [
        'legacy_trapped_id',
        'captor_village_id',
        'owner_user_id',
        'unit_composition',
        'status',
        'captured_at',
    ]))->toBeTrue();
});
