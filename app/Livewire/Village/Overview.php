<?php

declare(strict_types=1);

namespace App\Livewire\Village;

use App\Models\Game\Village;
use App\Services\Game\VillageQueueService;
use App\Services\Game\VillageResourceService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

/**
 * Livewire translation of the legacy `Dorf1Ctrl` resource overview.
 *
 * Feature parity goals carried over from the PHP batch template:
 * - present per-hour production totals alongside base, field, and bonus
 *   contributions so boosting logic remains transparent.
 * - surface active construction queue metadata to mirror the original upper
 *   right "currently building" widget.
 * - respect sitter access (render-only for restricted delegates) by exposing
 *   state through services instead of global session helpers.
 */
class Overview extends Component
{
    use AuthorizesRequests;

    public Village $village;

    /**
     * Resource snapshot keyed by resource name.
     *
     * @var array<string, mixed>
     */
    public array $resources = [];

    /**
     * Building queue summary keyed by `entries`, `active_entry`, etc.
     *
     * @var array<string, mixed>
     */
    public array $queue = [];

    private VillageResourceService $resourceService;

    private VillageQueueService $queueService;

    public function boot(
        VillageResourceService $resourceService,
        VillageQueueService $queueService,
    ): void {
        $this->resourceService = $resourceService;
        $this->queueService = $queueService;
    }

    public function mount(Village $village): void
    {
        $this->authorize('viewResources', $village);

        $this->village = $village->loadMissing('resources');
        $this->refreshSnapshots();
    }

    public function refreshSnapshots(): void
    {
        $this->authorize('viewResources', $this->village);

        $this->village->refresh();
        $this->resources = $this->resourceService->snapshot($this->village);
        $this->queue = $this->queueService->summarize($this->village);
        $this->dispatch('buildingQueue:refresh');
    }

    public function render(): View
    {
        return view('livewire.village.overview');
    }
}
