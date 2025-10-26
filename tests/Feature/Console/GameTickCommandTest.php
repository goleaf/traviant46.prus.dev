<?php

declare(strict_types=1);

use App\Jobs\CropStarvationJob;
use App\Jobs\MovementResolverJob;
use App\Jobs\OasisRespawnJob;
use App\Jobs\ResourceTickJob;
use App\Jobs\Shard\QueueCompleterJob;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;

it('dispatches shard jobs for the game tick', function (): void {
    Bus::fake();

    $exitCode = Artisan::call('game:tick');

    expect($exitCode)->toBe(0);

    Bus::assertDispatched(ResourceTickJob::class);
    Bus::assertDispatched(QueueCompleterJob::class);
    Bus::assertDispatched(MovementResolverJob::class);
    Bus::assertDispatched(OasisRespawnJob::class);
    Bus::assertDispatched(CropStarvationJob::class);
});

it('can dispatch shard jobs synchronously when requested', function (): void {
    Bus::fake();

    $exitCode = Artisan::call('game:tick', ['--sync' => true]);

    expect($exitCode)->toBe(0);

    Bus::assertDispatchedSync(ResourceTickJob::class);
    Bus::assertDispatchedSync(QueueCompleterJob::class);
    Bus::assertDispatchedSync(MovementResolverJob::class);
    Bus::assertDispatchedSync(OasisRespawnJob::class);
    Bus::assertDispatchedSync(CropStarvationJob::class);
});
