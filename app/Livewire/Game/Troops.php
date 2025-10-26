<?php

declare(strict_types=1);

namespace App\Livewire\Game;

use App\Actions\Game\TrainTroopsAction;
use App\Models\Game\TroopType;
use App\Models\Game\Village;
use App\Services\Game\TroopOverviewService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Livewire\Attributes\On;
use Livewire\Component;
use Throwable;

class Troops extends Component
{
    use AuthorizesRequests;

    public Village $village;

    /**
     * @var array<string, mixed>
     */
    public array $garrison = [];

    /**
     * @var array<string, mixed>
     */
    public array $queue = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $availableUnits = [];

    /**
     * @var array<string, string>
     */
    public array $trainingBuildings = [];

    /**
     * @var array{unit_type_id: int|null, quantity: int|null, training_building: string|null}
     */
    public array $form = [
        'unit_type_id' => null,
        'quantity' => null,
        'training_building' => null,
    ];

    public ?string $statusMessage = null;

    public ?string $errorMessage = null;

    private TroopOverviewService $overviewService;

    private TrainTroopsAction $trainTroops;

    public function boot(
        TroopOverviewService $overviewService,
        TrainTroopsAction $trainTroops,
    ): void {
        $this->overviewService = $overviewService;
        $this->trainTroops = $trainTroops;
    }

    public function mount(Village $village): void
    {
        $this->authorize('manageTroops', $village);

        $this->village = $village->loadMissing('owner');
        $this->trainingBuildings = $this->overviewService->trainingBuildingOptions();

        $this->refreshOverview();
        $this->resetTrainForm();
    }

    #[On('troops:refresh')]
    public function refreshOverview(): void
    {
        $this->authorize('manageTroops', $this->village);

        $this->village->refresh();
        $this->garrison = $this->overviewService->garrisonSummary($this->village);
        $this->queue = $this->overviewService->trainingQueue($this->village);
        $this->availableUnits = $this->overviewService->availableUnits($this->village);

        $this->syncDefaultUnitSelection();
    }

    #[On('troops:queue:refresh')]
    public function refreshQueue(): void
    {
        $this->authorize('manageTroops', $this->village);

        $this->village->refresh();
        $this->queue = $this->overviewService->trainingQueue($this->village);
    }

    public function dismissMessage(): void
    {
        $this->statusMessage = null;
        $this->errorMessage = null;
    }

    public function train(): void
    {
        $this->authorize('manageTroops', $this->village);

        $validated = $this->validate();

        $unitTypeId = isset($validated['form']['unit_type_id'])
            ? (int) $validated['form']['unit_type_id']
            : null;

        $quantity = isset($validated['form']['quantity'])
            ? (int) $validated['form']['quantity']
            : 0;

        $trainingBuilding = $validated['form']['training_building'] ?? null;

        if ($unitTypeId === null) {
            $this->addError('form.unit_type_id', __('Select a troop type to train.'));

            return;
        }

        $troopType = TroopType::query()->find($unitTypeId);

        if ($troopType === null) {
            $this->addError('form.unit_type_id', __('The selected troop type is no longer available.'));
            $this->syncDefaultUnitSelection();

            return;
        }

        try {
            $this->trainTroops->execute(
                $this->village,
                $troopType,
                $quantity,
                $trainingBuilding ?: null,
                [
                    'requested_by' => auth()->id(),
                ],
            );

            $this->statusMessage = __('Queued :quantity :unit for training.', [
                'quantity' => number_format($quantity),
                'unit' => $troopType->name,
            ]);
            $this->errorMessage = null;

            $this->refreshOverview();
            $this->resetTrainForm();

            $this->dispatch('troops:queueUpdated');
        } catch (InvalidArgumentException $exception) {
            $this->addError('form.quantity', $exception->getMessage());
            $this->errorMessage = $exception->getMessage();
            $this->statusMessage = null;
        } catch (Throwable $exception) {
            report($exception);
            $this->errorMessage = __('Unable to queue training right now. Please try again soon.');
            $this->statusMessage = null;
        }
    }

    protected function rules(): array
    {
        $unitRules = ['required', 'integer'];
        if ($this->availableUnits !== []) {
            $unitRules[] = Rule::in(array_map('intval', array_keys($this->availableUnits)));
        }

        $buildingRules = ['nullable', 'string', 'max:50'];
        if ($this->trainingBuildings !== []) {
            $buildingRules[] = Rule::in(array_keys($this->trainingBuildings));
        }

        return [
            'form.unit_type_id' => $unitRules,
            'form.quantity' => ['required', 'integer', 'min:1', 'max:50000'],
            'form.training_building' => $buildingRules,
        ];
    }

    public function render(): View
    {
        return view('livewire.game.troops');
    }

    private function resetTrainForm(): void
    {
        $this->form['quantity'] = null;
        $this->form['training_building'] = null;
        $this->syncDefaultUnitSelection();
    }

    private function syncDefaultUnitSelection(): void
    {
        if ($this->availableUnits === []) {
            $this->form['unit_type_id'] = null;

            return;
        }

        $current = $this->form['unit_type_id'] ?? null;
        $currentId = is_numeric($current) ? (int) $current : null;

        if ($currentId === null || ! array_key_exists($currentId, $this->availableUnits)) {
            $this->form['unit_type_id'] = (int) array_key_first($this->availableUnits);

            return;
        }

        $this->form['unit_type_id'] = $currentId;
    }
}
