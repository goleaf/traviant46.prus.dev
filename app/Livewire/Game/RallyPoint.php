<?php

declare(strict_types=1);

namespace App\Livewire\Game;

use App\Enums\Game\MovementOrderStatus;
use App\Events\Game\MovementCreated;
use App\Events\Game\TroopsArrived;
use App\Models\Game\MovementOrder;
use App\Models\Game\Village;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;

class RallyPoint extends Component
{
    use AuthorizesRequests;

    public Village $village;

    /**
     * @var list<array<string, mixed>>
     */
    public array $outgoing = [];

    /**
     * @var list<array<string, mixed>>
     */
    public array $incoming = [];

    public string $timezone = '';

    private string $channel = '';

    /**
     * @var array<int, string>
     */
    private const RESOURCE_KEYS = ['wood', 'clay', 'iron', 'crop'];

    public function mount(Village $village): void
    {
        $this->authorize('viewRallyPoint', $village);

        $this->village = $village->loadMissing([
            'movements.targetVillage.owner',
            'incomingMovements.originVillage.owner',
        ]);

        $this->timezone = auth()->user()?->timezone ?? config('app.timezone');
        $this->channel = $this->resolveChannel();

        $this->hydrateMovements();
    }

    /**
     * @return array<int, string>
     */
    public function getListeners(): array
    {
        $channel = $this->channel !== '' ? $this->channel : $this->resolveChannel();

        return [
            "echo-private:{$channel},".MovementCreated::EVENT => 'handleMovementBroadcast',
            "echo-private:{$channel},".TroopsArrived::EVENT => 'handleMovementBroadcast',
        ];
    }

    public function refreshMovements(): void
    {
        $this->authorize('viewRallyPoint', $this->village);

        $this->village->load([
            'movements.targetVillage.owner',
            'incomingMovements.originVillage.owner',
        ]);

        $this->hydrateMovements();
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function handleMovementBroadcast(array $payload = []): void
    {
        // The payload is intentionally ignored for now—refresh to ensure parity.
        $this->refreshMovements();
    }

    public function cancelMovement(int $movementId): void
    {
        $this->authorize('manageRallyPoint', $this->village);

        /** @var MovementOrder|null $movement */
        $movement = $this->village->movements()
            ->with('targetVillage.owner')
            ->find($movementId);

        if (! $movement instanceof MovementOrder) {
            $this->refreshMovements();
            $this->addError('movements', __('That movement is no longer available.'));

            return;
        }

        if (! $this->isCancellable($movement)) {
            $this->refreshMovements();
            $this->addError('movements', __('This movement can no longer be cancelled.'));

            return;
        }

        if (! $this->attemptCancellation($movement)) {
            $this->refreshMovements();
            $this->addError('movements', __('This movement can no longer be cancelled.'));

            return;
        }

        $this->refreshMovements();
    }

    private function attemptCancellation(MovementOrder $movement): bool
    {
        $cancelled = false;

        DB::transaction(function () use ($movement, &$cancelled): void {
            /** @var MovementOrder|null $locked */
            $locked = MovementOrder::query()
                ->whereKey($movement->getKey())
                ->lockForUpdate()
                ->first();

            if (! $locked instanceof MovementOrder) {
                return;
            }

            if (! $this->isCancellable($locked)) {
                return;
            }

            $this->applyCancellation($locked);
            $cancelled = true;
        }, 5);

        return $cancelled;
    }

    private function applyCancellation(MovementOrder $movement): void
    {
        $now = Carbon::now();
        $elapsedSeconds = $this->elapsedSinceDeparture($movement, $now);
        $refundedResources = $this->refundPayloadResources($movement);
        $cancelWindow = (int) config('game.movements.cancel_window_seconds', 0);

        $movement->status = MovementOrderStatus::Cancelled;
        $movement->arrive_at = null;
        $movement->return_at = $elapsedSeconds > 0
            ? $now->copy()->addSeconds($elapsedSeconds)
            : $now;
        $movement->processed_at = $now;

        $cancellationMetadata = [
            'cancelled_at' => $now->toIso8601String(),
            'elapsed_seconds' => $elapsedSeconds,
            'cancel_window_seconds' => $cancelWindow,
        ];

        if ($refundedResources !== []) {
            $cancellationMetadata['refunded_resources'] = $refundedResources;
        }

        $movement->metadata = $this->mergeMovementMetadata(
            is_array($movement->metadata) ? $movement->metadata : [],
            ['cancellation' => $cancellationMetadata],
        );

        $movement->save();
    }

    private function elapsedSinceDeparture(MovementOrder $movement, Carbon $now): int
    {
        $departAt = $movement->depart_at;

        if (! $departAt instanceof Carbon) {
            return 0;
        }

        return max(0, (int) $departAt->diffInSeconds($now, false));
    }

    /**
     * @return array<string, int>
     */
    private function refundPayloadResources(MovementOrder $movement): array
    {
        $bundle = $this->normaliseResourceBundle($movement->payload['resources'] ?? null);

        if ($bundle === []) {
            return [];
        }

        $originVillageId = (int) $movement->origin_village_id;

        if ($originVillageId <= 0) {
            return [];
        }

        /** @var Village|null $village */
        $village = Village::query()
            ->whereKey($originVillageId)
            ->lockForUpdate()
            ->first();

        if (! $village instanceof Village) {
            return [];
        }

        $balances = $this->resourceBalances($village);

        foreach ($bundle as $resource => $amount) {
            $balances[$resource] += $amount;
        }

        $village->forceFill(['resource_balances' => $balances])->save();

        return $bundle;
    }

    /**
     * @return array<string, int>
     */
    private function resourceBalances(Village $village): array
    {
        $source = (array) ($village->resource_balances ?? []);
        $balances = array_fill_keys(self::RESOURCE_KEYS, 0);

        foreach (self::RESOURCE_KEYS as $resource) {
            $value = $source[$resource] ?? 0;
            $balances[$resource] = is_numeric($value) ? (int) $value : 0;
        }

        return $balances;
    }

    /**
     * @return array<string, int>
     */
    private function normaliseResourceBundle(mixed $raw): array
    {
        if (! is_iterable($raw)) {
            return [];
        }

        $normalised = array_fill_keys(self::RESOURCE_KEYS, 0);

        foreach ($raw as $key => $value) {
            $resource = $this->resolveResourceKey($key);

            if ($resource === null || ! is_numeric($value)) {
                continue;
            }

            $amount = (int) $value;

            if ($amount <= 0) {
                continue;
            }

            $normalised[$resource] += $amount;
        }

        return array_filter(
            $normalised,
            static fn (int $amount): bool => $amount > 0,
        );
    }

    private function resolveResourceKey(int|string $key): ?string
    {
        if (is_int($key)) {
            return match ($key) {
                0 => 'wood',
                1 => 'clay',
                2 => 'iron',
                3 => 'crop',
                default => null,
            };
        }

        $normalised = strtolower((string) $key);

        return in_array($normalised, self::RESOURCE_KEYS, true) ? $normalised : null;
    }

    /**
     * @param array<string, mixed>|null $metadata
     * @param array<string, mixed> $values
     * @return array<string, mixed>
     */
    private function mergeMovementMetadata(?array $metadata, array $values): array
    {
        $base = (array) $metadata;

        return array_replace_recursive($base, $values);
    }

    public function render(): View
    {
        return view('livewire.game.rally-point');
    }

    private function resolveChannel(): string
    {
        return sprintf('game.village.%d', $this->village->getKey());
    }

    private function hydrateMovements(): void
    {
        $now = Carbon::now();

        $this->outgoing = $this->village->movements
            ->sortBy(fn (MovementOrder $movement) => $movement->arrive_at ?? $movement->depart_at)
            ->map(fn (MovementOrder $movement): array => $this->presentMovement($movement, true, $now))
            ->values()
            ->all();

        $this->incoming = $this->village->incomingMovements
            ->sortBy(fn (MovementOrder $movement) => $movement->arrive_at ?? $movement->depart_at)
            ->map(fn (MovementOrder $movement): array => $this->presentMovement($movement, false, $now))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function presentMovement(MovementOrder $movement, bool $outgoing, Carbon $now): array
    {
        $reference = $this->resolveReferenceTime($movement, $outgoing);

        $remainingSeconds = $reference instanceof Carbon
            ? max(0, (int) $now->diffInSeconds($reference, false))
            : null;

        $statusValue = $this->statusValue($movement);
        $statusMeta = $this->resolveStatusMeta($statusValue);

        return [
            'id' => (int) $movement->getKey(),
            'movement_type' => (string) $movement->movement_type,
            'mission' => $movement->mission,
            'status' => $statusValue,
            'status_label' => $statusMeta['label'],
            'status_color' => $statusMeta['color'],
            'status_variant' => $statusMeta['variant'],
            'depart_at' => $movement->depart_at?->toIso8601String(),
            'arrive_at' => $movement->arrive_at?->toIso8601String(),
            'return_at' => $movement->return_at?->toIso8601String(),
            'remaining_seconds' => $remainingSeconds,
            'remaining_label' => $this->formatRemainingLabel($remainingSeconds),
            'village_name' => $outgoing
                ? $movement->targetVillage?->name
                : $movement->originVillage?->name,
            'owner_name' => $outgoing
                ? $movement->targetVillage?->owner?->name
                : $movement->originVillage?->owner?->name,
            'coordinates' => $outgoing
                ? $movement->targetVillage?->coordinates
                : $movement->originVillage?->coordinates,
            'can_cancel' => $outgoing && $this->isCancellable($movement),
        ];
    }

    private function resolveReferenceTime(MovementOrder $movement, bool $outgoing): ?Carbon
    {
        $status = $this->statusValue($movement);

        if ($status === MovementOrderStatus::Cancelled->value) {
            return $movement->return_at instanceof Carbon ? $movement->return_at : null;
        }

        if (in_array($status, [MovementOrderStatus::Completed->value, MovementOrderStatus::Failed->value], true)) {
            return null;
        }

        if ($movement->arrive_at instanceof Carbon) {
            return $movement->arrive_at;
        }

        if ($outgoing && $movement->depart_at instanceof Carbon) {
            return $movement->depart_at;
        }

        return null;
    }

    /**
     * @return array{label: string, color: string, variant: string}
     */
    private function resolveStatusMeta(string $status): array
    {
        return match ($status) {
            'pending' => [
                'label' => __('Pending'),
                'color' => 'slate',
                'variant' => 'subtle',
            ],
            'in_transit' => [
                'label' => __('In transit'),
                'color' => 'sky',
                'variant' => 'solid',
            ],
            'completed' => [
                'label' => __('Completed'),
                'color' => 'emerald',
                'variant' => 'solid',
            ],
            'processing' => [
                'label' => __('Resolving'),
                'color' => 'amber',
                'variant' => 'solid',
            ],
            'failed' => [
                'label' => __('Failed'),
                'color' => 'rose',
                'variant' => 'subtle',
            ],
            'cancelled' => [
                'label' => __('Cancelled'),
                'color' => 'rose',
                'variant' => 'subtle',
            ],
            default => [
                'label' => Str::headline((string) $status),
                'color' => 'slate',
                'variant' => 'subtle',
            ],
        };
    }

    private function isCancellable(MovementOrder $movement): bool
    {
        $status = $this->statusValue($movement);

        if (in_array($status, [
            MovementOrderStatus::Completed->value,
            MovementOrderStatus::Cancelled->value,
            MovementOrderStatus::Processing->value,
            MovementOrderStatus::Failed->value,
        ], true)) {
            return false;
        }

        $window = (int) config('game.movements.cancel_window_seconds', 0);

        if ($window <= 0) {
            return false;
        }

        $departAt = $movement->depart_at;

        if (! $departAt instanceof Carbon) {
            return false;
        }

        $now = Carbon::now();
        $elapsed = $departAt->diffInSeconds($now, false);

        if ($elapsed > $window) {
            return false;
        }

        if ($movement->arrive_at instanceof Carbon && $movement->arrive_at->isPast()) {
            return false;
        }

        return true;
    }

    private function statusValue(MovementOrder $movement): string
    {
        $status = $movement->status;

        return $status instanceof MovementOrderStatus ? $status->value : (string) $status;
    }

    /**
     * Provide a textual countdown for the UI so tests and accessibility tools can read the timer.
     */
    private function formatRemainingLabel(?int $remainingSeconds): ?string
    {
        if ($remainingSeconds === null) {
            return null;
        }

        // Always clamp to a non-negative value so the string never shows negative times.
        $seconds = max(0, $remainingSeconds);

        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remainder = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $remainder);
        }

        return sprintf('%d:%02d', $minutes, $remainder);
    }
}
