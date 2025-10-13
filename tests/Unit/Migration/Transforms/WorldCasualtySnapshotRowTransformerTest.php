<?php

namespace Tests\Unit\Migration\Transforms;

use App\Services\Migration\Transforms\WorldCasualtySnapshotRowTransformer;
use InvalidArgumentException;
use Tests\TestCase;

class WorldCasualtySnapshotRowTransformerTest extends TestCase
{
    public function test_transform_maps_world_casualty_row(): void
    {
        $payload = WorldCasualtySnapshotRowTransformer::transform([
            'attacks' => 42,
            'casualties' => 5_000,
            'time' => 1_700_000_300,
        ]);

        $this->assertSame(42, $payload['attack_count']);
        $this->assertSame(5_000, $payload['casualty_count']);
        $this->assertSame(1_700_000_300, $payload['recorded_at']->getTimestamp());
    }

    public function test_transform_requires_positive_timestamp(): void
    {
        $this->expectException(InvalidArgumentException::class);

        WorldCasualtySnapshotRowTransformer::transform([
            'attacks' => 1,
            'casualties' => 1,
            'time' => 0,
        ]);
    }
}
