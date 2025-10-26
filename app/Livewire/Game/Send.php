<?php

declare(strict_types=1);

namespace App\Livewire\Game;

use App\Actions\Game\CreateMovementAction;
use App\Data\Game\MovementPreview;
use App\Enums\AttackMissionType;
use App\Models\Game\Village;
use App\Repositories\Game\VillageRepository;
use App\Services\Game\MovementCalculator;
use App\ValueObjects\Travian\MapSize;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Send extends Component
{
    use AuthorizesRequests;

    public Village $village;

    /**
     * @var array<int, array{id:int,name:string,quantity:int,speed:?int,upkeep:int}>
     */
    public array $availableUnits = [];

    /**
     * @var array<int, int>
     */
    public array $formUnits = [];

    public ?string $targetX = null;

    public ?string $targetY = null;

    public string $mission = 'attack';

    /**
     * Preview payload for blade rendering.
     *
     * @var array<string, mixed>|null
     */
    public ?array $preview = null;

    /**
     * Target village summary containing id/name/owner.
     *
     * @var array<string, mixed>|null
     */
    public ?array $targetVillage = null;

    public ?string $statusMessage = null;

    private VillageRepository $villages;

    private MovementCalculator $movementCalculator;

    private int $mapRadius = 400;

    private ?MovementPreview $previewData = null;

    public function boot(
        VillageRepository $villages,
        MovementCalculator $movementCalculator,
        MapSize $mapSize,
    ): void {
        $this->villages = $villages;
        $this->movementCalculator = $movementCalculator;

        $radius = $mapSize->toInt();
        $this->mapRadius = $radius > 0 ? $radius : 400;
    }

    public function mount(Village $village): void
    {
        $this->authorize('manageRallyPoint', $village);

        $this->village = $village->loadMissing(['units.unitType']);

        $this->availableUnits = $this->village->units
            ->map(function ($unit): array {
                $type = $unit->unitType;

                return [
                    'id' => (int) $unit->unit_type_id,
                    'name' => $type?->name ?? __('Unit :id', ['id' => $unit->unit_type_id]),
                    'quantity' => (int) $unit->quantity,
                    'speed' => $type?->speed,
                    'upkeep' => (int) ($type?->upkeep ?? 0),
                ];
            })
            ->filter(static fn (array $entry): bool => $entry['quantity'] > 0)
            ->sortBy('id')
            ->values()
            ->all();

        $this->formUnits = collect($this->availableUnits)
            ->mapWithKeys(static fn (array $entry) => [$entry['id'] => 0])
            ->all();
    }

    public function updated(string $property): void
    {
        if (
            str_starts_with($property, 'formUnits.')
            || in_array($property, ['targetX', 'targetY', 'mission'], true)
        ) {
            $this->statusMessage = null;
            $this->recalculatePreview();
        }
    }

    public function submit(CreateMovementAction $createMovement): void
    {
        $this->authorize('manageRallyPoint', $this->village);

        $this->resetErrorBag();

        $this->validate($this->rules());

        $this->recalculatePreview();

        $availability = collect($this->availableUnits)
            ->mapWithKeys(static fn (array $entry) => [(int) $entry['id'] => (int) $entry['quantity']])
            ->all();

        $selectedUnits = collect($this->formUnits)
            ->mapWithKeys(static function ($value, $key) use ($availability): array {
                $unitId = (int) $key;
                $value = (int) $value;
                $available = $availability[$unitId] ?? 0;

                return [$unitId => max(0, min($value, $available))];
            })
            ->filter(static fn (int $value): bool => $value > 0)
            ->all();

        if ($selectedUnits === []) {
            $this->addError('formUnits', __('Select at least one unit to dispatch.'));

            return;
        }

        if ($this->targetX === null || $this->targetY === null) {
            $this->addError('targetX', __('Enter valid coordinates.'));

            return;
        }

        $targetX = (int) $this->targetX;
        $targetY = (int) $this->targetY;

        $targetVillage = $this->villages->findByCoordinates($targetX, $targetY);

        if ($targetVillage === null) {
            $this->addError('targetX', __('No village exists at those coordinates.'));

            return;
        }

        if ($targetVillage->is($this->village)) {
            $this->addError('targetX', __('You cannot send troops to the current village.'));

            return;
        }

        if ($this->previewData === null) {
            $this->addError('formUnits', __('Unable to calculate travel time. Adjust your selection and try again.'));

            return;
        }

        $departAt = $this->previewData->departAt->copy();
        $arriveAt = $this->previewData->arriveAt->copy();

        $payload = [
            'units' => $selectedUnits,
            'mission' => $this->mission,
            'calculation' => [
                'unit_speed' => $this->previewData->slowestUnitSpeed,
                'slowest_unit_speed' => $this->previewData->slowestUnitSpeed,
                'speed_factor' => $this->previewData->speedFactor,
                'world_speed_factor' => $this->previewData->worldSpeedFactor,
                'unit_speeds' => $this->previewData->unitSpeeds,
                'wrap_around' => $this->previewData->wrapAround,
            ],
            'depart_at' => $departAt,
            'metadata' => [
                'preview' => $this->preview,
                'mission_code' => $this->resolveMissionEnum($this->mission)->value,
            ],
            'user_id' => auth()->id(),
        ];

        $movement = $createMovement->execute(
            origin: $this->village,
            target: $targetVillage,
            movementType: 'troops',
            payload: $payload,
        );

        $totalUnits = array_sum($selectedUnits);
        $eta = $this->formatDuration($this->previewData->durationSeconds);
        $destination = $targetVillage->name ?: __('Village (:x|:y)', [
            'x' => $targetVillage->x_coordinate,
            'y' => $targetVillage->y_coordinate,
        ]);

        $this->statusMessage = __('Movement queued: :count units en route to :target (ETA :eta).', [
            'count' => $totalUnits,
            'target' => $destination,
            'eta' => $eta,
        ]);

        $this->formUnits = collect($this->formUnits)->map(static fn () => 0)->all();
        $this->recalculatePreview();

        $this->dispatch('movement-created', id: $movement->getKey());
    }

    public function render(): View
    {
        return view('livewire.game.send')
            ->layout('layouts.game');
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $limit = $this->mapRadius;

        return [
            'targetX' => ['required', 'integer', "between:-{$limit},{$limit}"],
            'targetY' => ['required', 'integer', "between:-{$limit},{$limit}"],
            'mission' => ['required', Rule::in(array_keys($this->missionOptions()))],
            'formUnits.*' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function missionOptions(): array
    {
        return [
            'attack' => __('Attack'),
            'raid' => __('Raid'),
            'reinforce' => __('Reinforce'),
            'scout' => __('Scout'),
        ];
    }

    private function recalculatePreview(): void
    {
        $this->previewData = null;
        $this->preview = null;
        $this->targetVillage = null;

        if ($this->targetX === null || $this->targetY === null) {
            return;
        }

        if (! is_numeric($this->targetX) || ! is_numeric($this->targetY)) {
            return;
        }

        $targetX = (int) $this->targetX;
        $targetY = (int) $this->targetY;

        $units = [];

        foreach ($this->availableUnits as $unit) {
            $requested = (int) ($this->formUnits[$unit['id']] ?? 0);
            $available = (int) $unit['quantity'];
            $quantity = max(0, min($requested, $available));

            $units[$unit['id']] = [
                'quantity' => $quantity,
                'speed' => $unit['speed'],
                'upkeep' => $unit['upkeep'],
            ];
        }

        $preview = $this->movementCalculator->preview(
            (int) $this->village->x_coordinate,
            (int) $this->village->y_coordinate,
            $targetX,
            $targetY,
            $units,
        );

        if ($preview === null) {
            return;
        }

        $this->previewData = $preview;
        $this->preview = [
            'distance' => $preview->distance,
            'duration_seconds' => $preview->durationSeconds,
            'depart_at' => $preview->departAt->toIso8601String(),
            'arrive_at' => $preview->arriveAt->toIso8601String(),
            'upkeep' => $preview->upkeep,
        ];

        $targetVillage = $this->villages->findByCoordinates($targetX, $targetY);

        if ($targetVillage !== null) {
            $this->targetVillage = [
                'id' => $targetVillage->getKey(),
                'name' => $targetVillage->name,
                'owner' => $targetVillage->owner?->name,
            ];
        }
    }

    private function resolveMissionEnum(string $mission): AttackMissionType
    {
        return match ($mission) {
            'raid' => AttackMissionType::Raid,
            'reinforce' => AttackMissionType::Reinforcement,
            'scout' => AttackMissionType::Spy,
            default => AttackMissionType::Normal,
        };
    }

    private function formatDuration(int $seconds): string
    {
        if ($seconds <= 0) {
            return __('instant');
        }

        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remainingSeconds = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $remainingSeconds);
        }

        return sprintf('%d:%02d', $minutes, $remainingSeconds);
    }
}
