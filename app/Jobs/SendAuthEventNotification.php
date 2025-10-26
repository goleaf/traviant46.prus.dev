<?php

namespace App\Jobs;

use App\Services\Security\AuthEventBroadcaster;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendAuthEventNotification implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        protected string $event,
        protected array $payload,
    ) {}

    public function handle(AuthEventBroadcaster $broadcaster): void
    {
        $broadcaster->dispatch($this->event, $this->payload);
    }
}
