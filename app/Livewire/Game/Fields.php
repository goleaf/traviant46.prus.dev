<?php

declare(strict_types=1);

namespace App\Livewire\Game;

use App\Actions\Game\EnqueueBuildAction;
use App\Models\Game\ResourceField;
use App\Models\Game\Village;
use App\Services\Game\VillageQueueService;
use App\Services\Game\VillageResourceFieldService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Arr;
use Livewire\Component;
use LogicException;
use Throwable;

class Fields extends Component
{
    use AuthorizesRequests;

    public Village $village;

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $fields = [];

    /**
     * @var array<string, mixed>
     */
    public array $queue = [];

    public ?string $notice = null;

    public string $noticeType = 'info';

    private VillageResourceFieldService $fieldService;

    private VillageQueueService $queueService;

    private EnqueueBuildAction $enqueueBuildAction;

    public function boot(
        VillageResourceFieldService $fieldService,
        VillageQueueService $queueService,
        EnqueueBuildAction $enqueueBuildAction,
    ): void {
        $this->fieldService = $fieldService;
        $this->queueService = $queueService;
        $this->enqueueBuildAction = $enqueueBuildAction;
    }

    public function mount(Village $village): void
    {
        $this->authorize('viewResources', $village);

        $this->village = $village->loadMissing([
            'resourceFields' => static fn ($query) => $query->orderBy('slot_number'),
            'buildings',
            'buildingUpgrades',
            'buildingUpgrades.buildingType',
        ]);

        $this->refreshSnapshots();
    }

    public function refreshSnapshots(): void
    {
        $this->authorize('viewResources', $this->village);

        $this->village->refresh()->loadMissing([
            'resourceFields' => static fn ($query) => $query->orderBy('slot_number'),
            'buildings',
            'buildingUpgrades',
            'buildingUpgrades.buildingType',
        ]);

        $this->queue = $this->queueService->summarize($this->village);
        $this->fields = $this->fieldService->snapshot($this->village);

        $this->dispatch('buildingQueue:refresh');
    }

    public function enqueueUpgrade(int $fieldId): void
    {
        /** @var ResourceField|null $field */
        $field = $this->village->resourceFields->firstWhere('id', $fieldId);

        if (! $field instanceof ResourceField) {
            return;
        }

        $snapshot = collect($this->fields)->firstWhere('id', $fieldId);

        if (Arr::get($snapshot, 'is_locked') === true) {
            $reasons = (array) Arr::get($snapshot, 'locked_reasons', []);
            $this->setNotice($reasons[0] ?? __('This field is currently locked.'), 'warning');

            return;
        }

        $buildingType = Arr::get($snapshot, 'building_type');

        if (! is_int($buildingType)) {
            $this->setNotice(__('Unable to determine the correct building type for this field.'), 'error');

            return;
        }

        try {
            $this->enqueueBuildAction->execute($this->village, $buildingType);
            $this->setNotice(__('Upgrade queued for :name.', [
                'name' => Arr::get($snapshot, 'name', __('Field')),
            ]), 'success');
        } catch (RuntimeException $exception) {
            $this->setNotice($exception->getMessage(), 'warning');
        } catch (LogicException) {
            $this->setNotice(__('Construction queue is not yet available.'), 'warning');
        } catch (Throwable $throwable) {
            report($throwable);
            $this->setNotice(__('Unable to enqueue upgrade: :message', [
                'message' => $throwable->getMessage(),
            ]), 'error');
        } finally {
            $this->refreshSnapshots();
        }
    }

    public function render(): View
    {
        return view('livewire.game.fields');
    }

    private function setNotice(string $message, string $type = 'info'): void
    {
        $this->notice = $message;
        $this->noticeType = $type;
    }
}
