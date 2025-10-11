<?php

namespace Tests\Feature\Jobs;

use App\Jobs\ProcessTradeRoutes;
use App\Models\Economy\TradeRoute;
use App\Models\Game\MovementOrder;
use App\Models\Game\Village;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcessTradeRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatches_due_trade_routes(): void
    {
        $user = User::factory()->create();

        $origin = Village::create([
            'owner_id' => $user->id,
            'name' => 'Origin',
            'population' => 10,
            'loyalty' => 100,
            'x_coordinate' => 0,
            'y_coordinate' => 0,
            'is_capital' => false,
            'storage' => [
                'wood' => 1_000,
                'clay' => 1_000,
                'iron' => 1_000,
                'crop' => 1_000,
            ],
        ]);

        $target = Village::create([
            'owner_id' => $user->id,
            'name' => 'Target',
            'population' => 10,
            'loyalty' => 100,
            'x_coordinate' => 5,
            'y_coordinate' => 5,
            'is_capital' => false,
            'storage' => [
                'wood' => 100,
                'clay' => 100,
                'iron' => 100,
                'crop' => 100,
            ],
        ]);

        $route = TradeRoute::create([
            'user_id' => $user->id,
            'origin_village_id' => $origin->id,
            'target_village_id' => $target->id,
            'resources' => [
                'wood' => 200,
                'clay' => 150,
                'iron' => 100,
                'crop' => 50,
            ],
            'dispatch_interval_minutes' => 30,
            'next_dispatch_at' => now()->subMinute(),
            'is_active' => true,
        ]);

        (new ProcessTradeRoutes())->handle();

        $route->refresh();
        $origin->refresh();

        $this->assertTrue($route->next_dispatch_at->isFuture());
        $this->assertSame(800, $origin->storage['wood']);
        $this->assertSame(850, $origin->storage['clay']);
        $this->assertSame(900, $origin->storage['iron']);
        $this->assertSame(950, $origin->storage['crop']);

        $order = MovementOrder::query()->first();
        $this->assertNotNull($order);
        $this->assertSame(MovementOrder::TYPE_TRADE, $order->movement_type);
        $this->assertSame(MovementOrder::STATUS_EN_ROUTE, $order->status);
        $this->assertEquals([
            'wood' => 200,
            'clay' => 150,
            'iron' => 100,
            'crop' => 50,
        ], $order->payload['resources']);
        $this->assertTrue($order->arrive_at->greaterThan(now()));
    }
}
