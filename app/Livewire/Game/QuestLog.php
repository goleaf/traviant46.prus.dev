<?php

declare(strict_types=1);

namespace App\Livewire\Game;

use App\Domain\Game\Quest\QuestLogService;
use App\Models\Game\Quest;
use App\Models\Game\QuestProgress;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class QuestLog extends Component
{
    /**
     * @var list<array<string, mixed>>
     */
    public array $tutorialQuests = [];

    /**
     * @var list<array<string, mixed>>
     */
    public array $dailyQuests = [];

    public ?string $statusMessage = null;

    private QuestLogService $questLog;

    public function boot(QuestLogService $questLog): void
    {
        $this->questLog = $questLog;
    }

    public function mount(): void
    {
        $this->refreshQuests();
    }

    public function refreshLog(): void
    {
        $this->refreshQuests();

        $this->statusMessage = __('Quest log refreshed.');
    }

    public function completeQuest(int $questId): void
    {
        $user = $this->resolveUser();

        /** @var Quest $quest */
        $quest = Quest::query()->find($questId);

        if (! $quest instanceof Quest) {
            throw ValidationException::withMessages([
                'quest' => __('The selected quest could not be found.'),
            ]);
        }

        $this->questLog->markCompleted($user, $quest);

        $this->refreshQuests();

        $this->statusMessage = __('Quest marked as complete. Rewards are now ready to claim.');
    }

    public function render(): View
    {
        return view('livewire.game.quest-log');
    }

    private function refreshQuests(): void
    {
        $user = $this->resolveUser();

        $overview = $this->questLog->overview($user);

        $this->tutorialQuests = $this->mapQuestPayloads($overview['tutorial']);
        $this->dailyQuests = $this->mapQuestPayloads($overview['daily']);
    }

    /**
     * @param list<array<string, mixed>> $quests
     * @return list<array<string, mixed>>
     */
    private function mapQuestPayloads(array $quests): array
    {
        return collect($quests)
            ->map(function (array $quest): array {
                $completedAt = $quest['completed_at'] ?? null;
                $quest['completed_human'] = is_string($completedAt) && $completedAt !== ''
                    ? Carbon::parse($completedAt)->diffForHumans()
                    : null;

                $quest['state_label'] = match ($quest['state'] ?? null) {
                    QuestProgress::STATE_COMPLETED => __('Completed'),
                    default => __('In progress'),
                };

                return $quest;
            })
            ->values()
            ->all();
    }

    private function resolveUser(): User
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            abort(403);
        }

        return $user;
    }
}
