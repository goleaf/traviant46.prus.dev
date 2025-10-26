<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\LoginActivity;
use App\Services\Security\MultiAccountDetector;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class RebuildMultiAccountAlerts implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout = 900;

    public function __construct(private readonly int $chunkSize = 500)
    {
        $this->onQueue('automation');
    }

    public function handle(MultiAccountDetector $detector): void
    {
        $chunkSize = $this->chunkSize;

        $detector->withoutNotifications(function (MultiAccountDetector $detector) use ($chunkSize): void {
            LoginActivity::query()
                ->orderBy('id')
                ->chunkById($chunkSize, function (Collection $activities) use ($detector): void {
                    $activities
                        ->sortBy(function (LoginActivity $activity) {
                            return $activity->logged_at ?? $activity->created_at ?? now();
                        })
                        ->each(static function (LoginActivity $activity) use ($detector): void {
                            $detector->record($activity);
                        });
                }, 'id');
        });
    }
}
