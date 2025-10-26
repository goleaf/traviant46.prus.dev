<?php

declare(strict_types=1);

namespace App\Notifications\Game;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class VillageStarvationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param array{x: int, y: int} $coordinates
     */
    public function __construct(
        public readonly int $villageId,
        public readonly string $villageName,
        public readonly array $coordinates,
        public readonly int $cropBalance,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'starvation',
            'village_id' => $this->villageId,
            'village_name' => $this->villageName,
            'coordinates' => $this->coordinates,
            'crop_balance' => $this->cropBalance,
            'triggered_at' => now()->toIso8601String(),
        ];
    }
}
