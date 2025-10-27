<?php

declare(strict_types=1);

namespace App\Domain\Game\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Base class for broadcastable domain events targeting private game channels.
 *
 * @phpstan-type BroadcastPayload array<string, mixed>
 */
abstract class PrivateBroadcastDomainEvent implements ShouldBroadcast
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param BroadcastPayload $payload
     */
    public function __construct(
        public readonly string $channelName,
        public readonly array $payload = [],
    ) {
    }

    abstract public static function eventName(): string;

    public function broadcastAs(): string
    {
        return static::eventName();
    }

    public function broadcastOn(): Channel
    {
        return new PrivateChannel($this->channelName);
    }

    /**
     * @return BroadcastPayload
     */
    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
