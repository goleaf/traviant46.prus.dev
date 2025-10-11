<?php

namespace App\Jobs;

use App\Models\Game\Medal;
use App\Models\Game\PlayerStatistic;
use App\Models\GameConfiguration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessMedals implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;
    public int $timeout = 120;
    public string $queue = 'automation';

    public function handle(): void
    {
        $configuration = GameConfiguration::current();

        if (!$configuration->shouldAwardMedals()) {
            return;
        }

        $categories = config('game.medals.categories', []);
        $limit = (int) config('game.medals.top_limit', 10);
        $awardedWeek = (int) now()->format('oW');

        DB::beginTransaction();

        try {
            foreach ($categories as $category => $column) {
                $this->awardCategory($category, $column, $limit, $awardedWeek);
            }

            $configuration->markMedalsAwarded();
            DB::commit();
        } catch (Throwable $throwable) {
            DB::rollBack();

            Log::error('Failed to process weekly medals.', [
                'exception' => $throwable,
            ]);

            throw $throwable;
        }
    }

    private function awardCategory(string $category, string $column, int $limit, int $awardedWeek): void
    {
        PlayerStatistic::query()
            ->orderByMetric($column)
            ->limit($limit)
            ->get()
            ->filter(fn (PlayerStatistic $statistic) => $statistic->{$column} > 0)
            ->values()
            ->each(function (PlayerStatistic $statistic, int $index) use ($category, $column, $awardedWeek): void {
                $rank = $index + 1;
                $points = $statistic->{$column};

                Medal::updateOrCreate(
                    [
                        'user_id' => $statistic->user_id,
                        'category' => $category,
                        'rank' => $rank,
                        'awarded_week' => $awardedWeek,
                    ],
                    [
                        'points' => $points,
                        'metadata' => [
                            'metric' => $column,
                        ],
                        'awarded_at' => now(),
                    ]
                );
            });

        Log::info('Medals awarded for category.', [
            'category' => $category,
            'week' => $awardedWeek,
        ]);
    }
}
