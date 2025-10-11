<?php

namespace App\Livewire\Hero;

use App\Models\Hero;
use App\Models\HeroAdventure;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Component;

class HeroAdventures extends Component
{
    public ?Hero $hero = null;

    public function mount(?Hero $hero = null, ?int $heroId = null): void
    {
        $this->hero = $this->resolveHero($hero, $heroId);
    }

    public function render(): View
    {
        $hero = $this->hero;
        $adventures = Collection::make($hero?->adventures ?? []);

        return view('livewire.hero.hero-adventures', [
            'hero' => $hero,
            'stats' => $this->summarize($adventures),
            'adventureGroups' => $this->groupAdventures($adventures),
        ]);
    }

    protected function resolveHero(?Hero $hero, ?int $heroId = null): ?Hero
    {
        if ($hero instanceof Hero) {
            return $hero->loadMissing($this->eagerLoads());
        }

        $query = Hero::query()->with($this->eagerLoads());

        if ($heroId !== null) {
            return $query->find($heroId);
        }

        return $query->first();
    }

    protected function eagerLoads(): array
    {
        return [
            'adventures' => fn (Builder $builder): Builder => $builder
                ->orderBy('status')
                ->orderBy('available_at')
                ->orderBy('started_at')
                ->orderBy('completed_at'),
        ];
    }

    protected function summarize(Collection $adventures): array
    {
        $available = $adventures->filter(fn (HeroAdventure $adventure): bool => $adventure->isAvailable());
        $inProgress = $adventures->filter(fn (HeroAdventure $adventure): bool => $adventure->isInProgress());
        $completed = $adventures->filter(fn (HeroAdventure $adventure): bool => $adventure->isCompleted());
        $failedOrExpired = $adventures->filter(fn (HeroAdventure $adventure): bool => $adventure->isFailed() || $adventure->isExpired());

        $nextAvailable = $available
            ->sortBy(fn (HeroAdventure $adventure): int => $adventure->available_at?->getTimestamp() ?? PHP_INT_MAX)
            ->first();

        $nextCompletion = $inProgress
            ->sortBy(fn (HeroAdventure $adventure): int => $adventure->completed_at?->getTimestamp() ?? PHP_INT_MAX)
            ->first();

        $lastCompleted = $completed
            ->sortByDesc(fn (HeroAdventure $adventure): int => $adventure->completed_at?->getTimestamp() ?? 0)
            ->first();

        return [
            'counts' => [
                'total' => $adventures->count(),
                'available' => $available->count(),
                'in_progress' => $inProgress->count(),
                'completed' => $completed->count(),
                'failed_or_expired' => $failedOrExpired->count(),
            ],
            'next_available_at' => $nextAvailable?->available_at,
            'next_completion_at' => $nextCompletion?->completed_at,
            'last_completed_at' => $lastCompleted?->completed_at,
            'aggregated_rewards' => $this->aggregateRewards($adventures),
        ];
    }

    protected function aggregateRewards(Collection $adventures): array
    {
        $totals = [];

        $adventures->each(function (HeroAdventure $adventure) use (&$totals): void {
            $rewards = $adventure->getAttribute('rewards');

            if (! is_array($rewards)) {
                return;
            }

            foreach ($rewards as $key => $value) {
                if (is_numeric($value)) {
                    $totals[$key] = ($totals[$key] ?? 0) + (float) $value;
                }
            }
        });

        ksort($totals);

        return $totals;
    }

    protected function groupAdventures(Collection $adventures): Collection
    {
        return $adventures
            ->sortBy(function (HeroAdventure $adventure): string {
                $timestamp = $adventure->available_at
                    ?? $adventure->started_at
                    ?? $adventure->completed_at
                    ?? $adventure->created_at
                    ?? Carbon::now();

                $timestampValue = $timestamp instanceof \DateTimeInterface
                    ? $timestamp->getTimestamp()
                    : (is_numeric($timestamp) ? (int) $timestamp : 0);

                return sprintf(
                    '%02d-%010d-%06d',
                    $adventure->statusPriority(),
                    max(0, $timestampValue),
                    $adventure->getKey() ?? 0,
                );
            })
            ->groupBy(fn (HeroAdventure $adventure): string => $adventure->status)
            ->map(function (Collection $group, string $status): array {
                return [
                    'status' => $status,
                    'label' => HeroAdventure::statusLabels()[$status] ?? ucfirst(str_replace('_', ' ', $status)),
                    'items' => $group->values(),
                ];
            })
            ->sortBy(function (array $group): int {
                $order = array_keys(HeroAdventure::statusLabels());
                $index = array_search($group['status'], $order, true);

                return $index === false ? PHP_INT_MAX : $index;
            })
            ->values();
    }
}
