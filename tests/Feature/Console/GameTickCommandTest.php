<?php

declare(strict_types=1);

use App\Jobs\Shard\CropStarvation;
use App\Jobs\Shard\MovementResolver;
use App\Jobs\Shard\OasisRespawn;
use App\Jobs\Shard\QueueCompleter;
use App\Jobs\Shard\ResourceTick;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;

it('dispatches shard jobs for the game tick', function (): void {
    Bus::fake();

    $exitCode = Artisan::call('game:tick');

    expect($exitCode)->toBe(0);

    Bus::assertDispatched(ResourceTick::class);
    Bus::assertDispatched(QueueCompleter::class);
    Bus::assertDispatched(MovementResolver::class);
    Bus::assertDispatched(OasisRespawn::class);
    Bus::assertDispatched(CropStarvation::class);
});

it('can dispatch shard jobs synchronously when requested', function (): void {
    Bus::fake();

    $exitCode = Artisan::call('game:tick', ['--sync' => true]);

    expect($exitCode)->toBe(0);

    Bus::assertDispatchedSync(ResourceTick::class);
    Bus::assertDispatchedSync(QueueCompleter::class);
    Bus::assertDispatchedSync(MovementResolver::class);
    Bus::assertDispatchedSync(OasisRespawn::class);
    Bus::assertDispatchedSync(CropStarvation::class);
});
