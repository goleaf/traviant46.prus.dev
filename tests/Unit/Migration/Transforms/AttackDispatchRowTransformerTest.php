<?php

namespace Tests\Unit\Migration\Transforms;

use App\Enums\AttackMissionType;
use App\Services\Migration\Transforms\AttackDispatchRowTransformer;
use InvalidArgumentException;
use Tests\TestCase;

class AttackDispatchRowTransformerTest extends TestCase
{
    public function test_transform_maps_attack_dispatch_row(): void
    {
        $payload = AttackDispatchRowTransformer::transform([
            'timestamp' => 1_700_000_000,
            'timestamp_checksum' => 'ABC123',
            'to_kid' => 123,
            'u1' => 10,
            'u2' => 0,
            'u3' => 5,
            'u4' => 0,
            'u5' => 0,
            'u6' => 0,
            'u7' => 0,
            'u8' => 0,
            'u9' => 0,
            'u10' => 1,
            'u11' => 1,
            'attack_type' => AttackMissionType::Raid->value,
            'redeployHero' => 1,
        ]);

        $this->assertSame(123, $payload['target_village_id']);
        $this->assertSame('2023-11-14 22:13:20', $payload['arrives_at']->toDateTimeString());
        $this->assertSame('ABC123', $payload['arrival_checksum']);
        $this->assertSame(10, $payload['unit_slot_one_count']);
        $this->assertInstanceOf(AttackMissionType::class, $payload['attack_type']);
        $this->assertTrue($payload['includes_hero']);
        $this->assertTrue($payload['redeploy_hero']);
    }

    public function test_transform_validates_required_fields(): void
    {
        $this->expectException(InvalidArgumentException::class);

        AttackDispatchRowTransformer::transform([
            'timestamp' => 0,
            'timestamp_checksum' => '',
            'to_kid' => 0,
            'attack_type' => 99,
        ]);
    }
}
