<?php

declare(strict_types=1);

namespace Tests\Unit\Migration\Transforms;

use App\Services\Migration\Transforms\GameSummaryMetricRowTransformer;
use Carbon\CarbonImmutable;
use InvalidArgumentException;
use Tests\TestCase;

class GameSummaryMetricRowTransformerTest extends TestCase
{
    public function test_transform_maps_summary_row(): void
    {
        $payload = GameSummaryMetricRowTransformer::transform([
            'players_count' => 10,
            'roman_players_count' => 4,
            'teuton_players_count' => 3,
            'gaul_players_count' => 2,
            'egyptians_players_count' => 1,
            'huns_players_count' => 0,
            'first_village_player_name' => 'Alice',
            'first_village_time' => 1_700_000_200,
        ]);

        $this->assertSame(10, $payload['total_player_count']);
        $this->assertSame('Alice', $payload['first_village_player_name']);
        $this->assertInstanceOf(CarbonImmutable::class, $payload['first_village_recorded_at']);
    }

    public function test_transform_requires_player_count(): void
    {
        $this->expectException(InvalidArgumentException::class);

        GameSummaryMetricRowTransformer::transform([]);
    }
}
