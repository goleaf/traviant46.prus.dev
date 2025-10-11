<?php

namespace Tests\Feature\Jobs;

use App\Jobs\ProcessMerchantReturn;
use App\Models\Game\MovementOrder;
use App\Models\Game\Village;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ProcessMerchantReturnTest extends TestCase
{
    use RefreshDatabase;

    public function test_merchants_deliver_and_return(): void
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

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
                'wood' => 500,
                'clay' => 500,
                'iron' => 500,
                'crop' => 500,
            ],
        ]);

        $target = Village::create([
            'owner_id' => $user->id,
            'name' => 'Target',
            'population' => 10,
            'loyalty' => 100,
            'x_coordinate' => 3,
            'y_coordinate' => 4,
            'is_capital' => false,
            'storage' => [
                'wood' => 0,
                'clay' => 0,
                'iron' => 0,
                'crop' => 0,
            ],
        ]);

        $order = MovementOrder::create([
            'user_id' => $user->id,
            'origin_village_id' => $origin->id,
            'target_village_id' => $target->id,
            'movement_type' => MovementOrder::TYPE_TRADE,
            'status' => MovementOrder::STATUS_EN_ROUTE,
            'depart_at' => now()->subMinutes(10),
            'arrive_at' => now()->subMinute(),
            'payload' => [
                'resources' => [
                    'wood' => 120,
                    'clay' => 80,
                    'iron' => 60,
                    'crop' => 40,
                ],
                'travel_minutes' => 10,
            ],
        ]);

        (new ProcessMerchantReturn())->handle();

        $order->refresh();
        $target->refresh();

        $this->assertSame(MovementOrder::TYPE_RETURN, $order->movement_type);
        $this->assertSame(MovementOrder::STATUS_EN_ROUTE, $order->status);
        $this->assertSame(120, $target->storage['wood']);
        $this->assertSame(80, $target->storage['clay']);
        $this->assertSame(60, $target->storage['iron']);
        $this->assertSame(40, $target->storage['crop']);
        $this->assertArrayHasKey('delivered_at', $order->payload);

        // Fast-forward to return arrival.
        Carbon::setTestNow(now()->addMinutes(11));

        (new ProcessMerchantReturn())->handle();

        $order->refresh();
        $this->assertSame(MovementOrder::STATUS_COMPLETED, $order->status);
        $this->assertArrayHasKey('returned_at', $order->payload);

        Carbon::setTestNow();
    }
}
