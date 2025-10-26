<?php

declare(strict_types=1);

namespace App\Livewire\Village;

use App\Models\Game\Village;
use App\Services\Game\VillageQueueService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Livewire adaptation of `OnLoadBuildingsDorfCtrl`.
 *
 * The widget keeps the building queue in sync across both Dorf1 and Dorf2
 * contexts, highlighting the active upgrade and providing refresh hooks for
 * instant-completion flows.
 */
class BuildingQueue extends Component
{
    public Village $village;

    /**
     * @var array<string, mixed>
     */
    public array $queue = [];

    private VillageQueueService $queueService;

    public function boot(VillageQueueService $queueService): void
    {
        $this->queueService = $queueService;
    }

    public function mount(Village $village): void
    {
        $this->village = $village;
        $this->refreshQueue();
    }

    #[On('buildingQueue:refresh')]
    public function refreshQueue(): void
    {
        $this->village->refresh();
        $this->queue = $this->queueService->summarize($this->village);
    }

    public function render(): View
    {
        return view('livewire.village.building-queue');
    }
}
