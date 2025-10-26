<?php

declare(strict_types=1);

namespace App\Events\Game;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MovementCreated implements ShouldBroadcast
{
    use Dispatchable;
    use SerializesModels;

    public const EVENT = 'movement-created';

    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public readonly string $channelName,
        public readonly array $payload = [],
    ) {}

    public function broadcastAs(): string
    {
        return self::EVENT;
    }

    public function broadcastOn(): Channel
    {
        return new PrivateChannel($this->channelName);
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
