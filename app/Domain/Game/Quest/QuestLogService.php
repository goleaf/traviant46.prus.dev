<?php

declare(strict_types=1);

namespace App\Domain\Game\Quest;

use App\Models\Game\Quest;
use App\Models\Game\QuestProgress;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class QuestLogService
{
    public function __construct(
        private readonly Quest $questModel,
        private readonly QuestProgress $progressModel,
    ) {
    }

    /**
     * @return array{tutorial: list<array<string, mixed>>, daily: list<array<string, mixed>>}
     */
    public function overview(User $user): array
    {
        /** @var EloquentCollection<int, Quest> $quests */
        $quests = $this->questModel->newQuery()
            ->orderBy('is_repeatable')
            ->orderBy('title')
            ->with([
                'progresses' => fn ($query) => $query->where('user_id', $user->getKey()),
            ])
            ->get();

        $mapped = $quests->map(function (Quest $quest) use ($user): array {
            $progress = $this->ensureProgress($user, $quest);

            return $this->formatQuest($quest, $progress);
        });

        return [
            'tutorial' => $mapped->where('is_daily', false)->values()->all(),
            'daily' => $mapped->where('is_daily', true)->values()->all(),
        ];
    }

    public function markCompleted(User $user, Quest $quest): array
    {
        $progress = $this->ensureProgress($user, $quest);

        $payload = is_array($progress->progress) ? $progress->progress : [];

        $objectives = collect($payload['objectives'] ?? [])
            ->map(function (array $objective): array {
                $objective['completed'] = true;
                $objective['completed_at'] = $objective['completed_at'] ?? now()->toIso8601String();

                return $objective;
            })
            ->values()
            ->all();

        $rewards = collect($payload['rewards'] ?? [])
            ->map(function (array $reward): array {
                $reward['claimable'] = true;

                return $reward;
            })
            ->values()
            ->all();

        $progress->markCompleted([
            'objectives' => $objectives,
            'rewards' => $rewards,
            'completed_at' => now()->toIso8601String(),
        ]);

        $progress->refresh();
        $quest->setRelation('progresses', collect([$progress]));

        return $this->formatQuest($quest, $progress);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatQuest(Quest $quest, QuestProgress $progress): array
    {
        $payload = is_array($progress->progress) ? $progress->progress : [];

        return [
            'id' => $quest->getKey(),
            'code' => $quest->quest_code,
            'title' => $quest->title,
            'description' => $quest->description,
            'state' => $progress->state,
            'objectives' => array_values((array) ($payload['objectives'] ?? [])),
            'rewards' => array_values((array) ($payload['rewards'] ?? [])),
            'completed_at' => $payload['completed_at'] ?? null,
            'is_daily' => $quest->isDaily(),
        ];
    }

    private function ensureProgress(User $user, Quest $quest): QuestProgress
    {
        if (! $quest->relationLoaded('progresses')) {
            $quest->load([
                'progresses' => fn ($query) => $query->where('user_id', $user->getKey()),
            ]);
        }

        $progress = $quest->progressFor($user);

        $objectives = $this->formatObjectives($quest->objectives());
        $rewards = $this->formatRewards($quest->rewards());

        if ($progress === null) {
            $progress = $quest->progresses()->create([
                'user_id' => $user->getKey(),
                'state' => QuestProgress::STATE_PENDING,
                'progress' => [
                    'objectives' => $objectives,
                    'rewards' => $rewards,
                ],
            ]);
        } else {
            $payload = is_array($progress->progress) ? $progress->progress : [];

            $payload['objectives'] = $this->mergeObjectives($objectives, (array) ($payload['objectives'] ?? []));
            $payload['rewards'] = $this->mergeRewards($rewards, (array) ($payload['rewards'] ?? []));

            if ($progress->progress !== $payload) {
                $progress->forceFill([
                    'progress' => $payload,
                ])->save();
            }
        }

        $quest->setRelation('progresses', collect([$progress]));

        return $progress->fresh();
    }

    /**
     * @param list<array<string, mixed>> $definitions
     * @return list<array<string, mixed>>
     */
    private function formatObjectives(array $definitions): array
    {
        return collect($definitions)
            ->values()
            ->map(function (array $definition, int $index): array {
                $key = $this->normaliseKey($definition, 'objective', $index);

                return [
                    'key' => $key,
                    'label' => (string) ($definition['label'] ?? $definition['title'] ?? $definition['description'] ?? __('Complete the objective')),
                    'description' => Arr::get($definition, 'description'),
                    'target' => Arr::get($definition, 'target'),
                    'completed' => (bool) ($definition['completed'] ?? false),
                    'progress' => Arr::get($definition, 'progress'),
                ];
            })
            ->all();
    }

    /**
     * @param list<array<string, mixed>> $definitions
     * @return list<array<string, mixed>>
     */
    private function formatRewards(array $definitions): array
    {
        return collect($definitions)
            ->values()
            ->map(function (array $definition, int $index): array {
                $key = $this->normaliseKey($definition, 'reward', $index);

                return [
                    'key' => $key,
                    'label' => (string) ($definition['label'] ?? __('Reward')),
                    'amount' => Arr::get($definition, 'amount'),
                    'details' => Arr::get($definition, 'details'),
                    'claimable' => (bool) ($definition['claimable'] ?? false),
                    'claimed' => (bool) ($definition['claimed'] ?? false),
                ];
            })
            ->all();
    }

    /**
     * @param list<array<string, mixed>> $base
     * @param list<array<string, mixed>> $existing
     * @return list<array<string, mixed>>
     */
    private function mergeObjectives(array $base, array $existing): array
    {
        $existingMap = $this->mapByKey($existing);

        return collect($base)
            ->map(function (array $objective) use ($existingMap): array {
                $current = $existingMap[$objective['key']] ?? null;

                if (! is_array($current)) {
                    return $objective;
                }

                $objective['completed'] = (bool) ($current['completed'] ?? $objective['completed']);
                $objective['progress'] = $current['progress'] ?? $objective['progress'] ?? null;
                $objective['completed_at'] = $current['completed_at'] ?? null;

                return $objective;
            })
            ->values()
            ->all();
    }

    /**
     * @param list<array<string, mixed>> $base
     * @param list<array<string, mixed>> $existing
     * @return list<array<string, mixed>>
     */
    private function mergeRewards(array $base, array $existing): array
    {
        $existingMap = $this->mapByKey($existing);

        return collect($base)
            ->map(function (array $reward) use ($existingMap): array {
                $current = $existingMap[$reward['key']] ?? null;

                if (! is_array($current)) {
                    return $reward;
                }

                $reward['claimable'] = (bool) ($current['claimable'] ?? $reward['claimable']);
                $reward['claimed'] = (bool) ($current['claimed'] ?? $reward['claimed']);
                $reward['details'] = $current['details'] ?? $reward['details'];
                $reward['amount'] = $current['amount'] ?? $reward['amount'];

                return $reward;
            })
            ->values()
            ->all();
    }

    /**
     * @param list<array<string, mixed>> $items
     * @return array<string, array<string, mixed>>
     */
    private function mapByKey(array $items): array
    {
        return collect($items)
            ->filter(fn ($item): bool => is_array($item) && isset($item['key']))
            ->keyBy(fn ($item) => (string) $item['key'])
            ->all();
    }

    private function normaliseKey(array $definition, string $prefix, int $index): string
    {
        $candidates = [
            $definition['key'] ?? null,
            $definition['id'] ?? null,
            $definition['code'] ?? null,
            $definition['label'] ?? null,
            $definition['title'] ?? null,
            $definition['description'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (! is_string($candidate) || trim($candidate) === '') {
                continue;
            }

            $slug = Str::snake(Str::slug($candidate));

            if ($slug !== '') {
                return $slug;
            }
        }

        return sprintf('%s_%d', $prefix, $index);
    }
}
