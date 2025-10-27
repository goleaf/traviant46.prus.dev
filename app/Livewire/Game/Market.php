<?php

declare(strict_types=1);

namespace App\Livewire\Game;

use App\Enums\SitterPermission;
use App\Models\Game\MarketOffer;
use App\Models\Game\Trade;
use App\Models\Game\Village;
use App\Models\User;
use App\Services\Game\MarketService;
use App\Support\Auth\SitterContext;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Computed;
use Livewire\Component;
use RuntimeException;

/**
 * Modernises the marketplace workflow with Livewire-powered offer management and trade dispatching.
 */
class Market extends Component
{
    use AuthorizesRequests;

    private const RESOURCE_KEYS = ['wood', 'clay', 'iron', 'crop'];

    public Village $village;

    /**
     * @var array<string, int>
     */
    public array $offerGive = [];

    /**
     * @var array<string, int>
     */
    public array $offerWant = [];

    /**
     * @var array<string, int>
     */
    public array $tradePayload = [];

    public ?int $tradeTarget = null;

    public ?string $statusMessage = null;

    public ?string $errorMessage = null;

    public bool $sitterReadOnly = false;

    private MarketService $marketService;

    public function boot(MarketService $marketService): void
    {
        $this->marketService = $marketService;
    }

    public function mount(Village $village): void
    {
        $this->authorize('viewResources', $village);

        $this->village = $village->loadMissing(['owner.villages']);

        $this->resetOfferForm();
        $this->resetTradeForm();

        $owner = $this->village->owner;

        if ($owner instanceof User) {
            $this->sitterReadOnly = SitterContext::isActingAsSitter()
                && ! SitterContext::hasPermission($owner, SitterPermission::Trade);
        }
    }

    #[Computed]
    public function merchantSummary(): array
    {
        return $this->marketService->summarizeMerchants($this->village->fresh());
    }

    #[Computed]
    public function ownOffers(): Collection
    {
        return $this->village->marketOffers()
            ->orderByDesc('created_at')
            ->get();
    }

    #[Computed]
    public function availableOffers(): Collection
    {
        return MarketOffer::query()
            ->with(['village.owner'])
            ->where('village_id', '!=', $this->village->getKey())
            ->latest()
            ->take(12)
            ->get();
    }

    #[Computed]
    public function outgoingTrades(): Collection
    {
        return Trade::query()
            ->with('targetVillage.owner')
            ->where('origin', $this->village->getKey())
            ->where('eta', '>', now()->subDay())
            ->orderBy('eta')
            ->get();
    }

    #[Computed]
    public function incomingTrades(): Collection
    {
        return Trade::query()
            ->with('originVillage.owner')
            ->where('target', $this->village->getKey())
            ->where('eta', '>', now()->subDay())
            ->orderBy('eta')
            ->get();
    }

    #[Computed]
    public function playerVillageOptions(): Collection
    {
        $owner = $this->village->owner;

        if (! $owner instanceof User) {
            return collect();
        }

        $owner->loadMissing('villages');

        return $owner->villages
            ->where('id', '!=', $this->village->getKey())
            ->sortBy('name')
            ->values();
    }

    #[Computed]
    public function offerMerchantsNeeded(): int
    {
        $summary = $this->merchantSummary();

        return $this->calculateRequiredMerchants(
            $this->normaliseResources($this->offerGive),
            $summary['capacity_per_merchant'] ?? 1,
        );
    }

    #[Computed]
    public function tradeMerchantsNeeded(): int
    {
        $summary = $this->merchantSummary();

        return $this->calculateRequiredMerchants(
            $this->normaliseResources($this->tradePayload),
            $summary['capacity_per_merchant'] ?? 1,
        );
    }

    #[Computed]
    public function tradeEtaPreview(): ?string
    {
        $target = $this->resolveTradeTarget();

        if (! $target instanceof Village) {
            return null;
        }

        if ($this->calculatePayloadTotal($this->tradePayload) === 0) {
            return null;
        }

        $summary = $this->merchantSummary();

        $eta = $this->calculateEta($this->village, $target, (float) ($summary['speed'] ?? 1.0));

        // Combine a friendly relative window with a deterministic absolute timestamp for the UI hint.
        return __('Arrives :relative (:absolute)', [
            'relative' => $eta->diffForHumans(),
            'absolute' => $eta->format('H:i'),
        ]);
    }

    public function createOffer(): void
    {
        $this->authorize('viewResources', $this->village);

        if (! $this->guardTradePermissions()) {
            return;
        }

        $this->resetMessages();

        $this->validateOfferPayload();

        $give = $this->normaliseResources($this->offerGive);
        $want = $this->normaliseResources($this->offerWant);

        if ($this->calculatePayloadTotal($give) === 0) {
            $this->addError('offerGive', __('Enter at least one resource to give.'));

            return;
        }

        if ($this->calculatePayloadTotal($want) === 0) {
            $this->addError('offerWant', __('Enter at least one resource to request.'));

            return;
        }

        try {
            $this->marketService->createOffer($this->village->fresh(), $give, $want);
            $this->statusMessage = __('Offer posted. Merchants are now waiting for a partner.');
            $this->resetOfferForm();
            $this->refreshVillage();
        } catch (RuntimeException $exception) {
            $this->errorMessage = $exception->getMessage();
        }
    }

    public function cancelOffer(int $offerId): void
    {
        $this->authorize('viewResources', $this->village);

        if (! $this->guardTradePermissions()) {
            return;
        }
        $this->resetMessages();

        $offer = $this->village->marketOffers()->findOrFail($offerId);

        $this->marketService->cancelOffer($offer);
        $this->statusMessage = __('Offer cancelled and resources returned to storage.');
        $this->refreshVillage();
    }

    public function acceptOffer(int $offerId): void
    {
        $this->authorize('viewResources', $this->village);

        if (! $this->guardTradePermissions()) {
            return;
        }
        $this->resetMessages();

        $offer = MarketOffer::query()->with('village')->findOrFail($offerId);

        try {
            $this->marketService->acceptOffer($offer, $this->village->fresh());
            $this->statusMessage = __('Offer accepted. Merchants are en route.');
            $this->refreshVillage();
        } catch (RuntimeException $exception) {
            $this->errorMessage = $exception->getMessage();
        }
    }

    public function sendTrade(): void
    {
        $this->authorize('viewResources', $this->village);

        if (! $this->guardTradePermissions()) {
            return;
        }

        $this->resetMessages();

        $this->validateTradePayload();

        $resources = $this->normaliseResources($this->tradePayload);

        if ($this->calculatePayloadTotal($resources) === 0) {
            $this->addError('tradePayload', __('Enter at least one resource to send.'));

            return;
        }

        $target = $this->resolveTradeTarget();

        if (! $target instanceof Village) {
            $this->addError('tradeTarget', __('Select a destination village.'));

            return;
        }

        try {
            $this->marketService->sendTrade($this->village->fresh(), $target, $resources);
            $this->statusMessage = __('Merchants dispatched. Track their progress below.');
            $this->resetTradeForm();
            $this->refreshVillage();
        } catch (RuntimeException $exception) {
            $this->errorMessage = $exception->getMessage();
        }
    }

    public function render(): View
    {
        return view('livewire.game.market');
    }

    private function resetMessages(): void
    {
        $this->statusMessage = null;
        $this->errorMessage = null;
        $this->resetValidation();
    }

    private function resetOfferForm(): void
    {
        $defaults = array_fill_keys(self::RESOURCE_KEYS, 0);

        $this->offerGive = $defaults;
        $this->offerWant = $defaults;
    }

    private function resetTradeForm(): void
    {
        $this->tradePayload = array_fill_keys(self::RESOURCE_KEYS, 0);
        $this->tradeTarget = null;
    }

    private function refreshVillage(): void
    {
        $this->village = $this->village->fresh()->loadMissing(['owner.villages']);
    }

    private function guardTradePermissions(): bool
    {
        if (! $this->sitterReadOnly) {
            return true;
        }

        $this->errorMessage = __('Sitter sessions without trade permission cannot manage the marketplace.');

        return false;
    }

    /**
     * @return array<string, int>
     */
    private function normaliseResources(array $resources): array
    {
        $normalized = array_fill_keys(self::RESOURCE_KEYS, 0);

        foreach ($resources as $resource => $value) {
            if (! in_array($resource, self::RESOURCE_KEYS, true)) {
                continue;
            }

            $normalized[$resource] = max(0, (int) $value);
        }

        return $normalized;
    }

    /**
     * @param array<string, int|string|null> $payload
     */
    private function calculatePayloadTotal(array $payload): int
    {
        return array_sum($this->normaliseResources($payload));
    }

    private function validateOfferPayload(): void
    {
        Validator::make(
            [
                'offerGive' => $this->offerGive,
                'offerWant' => $this->offerWant,
            ],
            [
                'offerGive.*' => ['nullable', 'integer', 'min:0'],
                'offerWant.*' => ['nullable', 'integer', 'min:0'],
            ],
            [],
            $this->attributeLabels(),
        )->validate();
    }

    private function validateTradePayload(): void
    {
        Validator::make(
            [
                'tradePayload' => $this->tradePayload,
                'tradeTarget' => $this->tradeTarget,
            ],
            [
                'tradePayload.*' => ['nullable', 'integer', 'min:0'],
                'tradeTarget' => ['required', 'integer', 'exists:villages,id'],
            ],
            [],
            $this->attributeLabels(),
        )->validate();
    }

    /**
     * @return array<string, string>
     */
    private function attributeLabels(): array
    {
        return [
            'offerGive.wood' => __('wood to give'),
            'offerGive.clay' => __('clay to give'),
            'offerGive.iron' => __('iron to give'),
            'offerGive.crop' => __('crop to give'),
            'offerWant.wood' => __('wood requested'),
            'offerWant.clay' => __('clay requested'),
            'offerWant.iron' => __('iron requested'),
            'offerWant.crop' => __('crop requested'),
            'tradePayload.wood' => __('wood to send'),
            'tradePayload.clay' => __('clay to send'),
            'tradePayload.iron' => __('iron to send'),
            'tradePayload.crop' => __('crop to send'),
            'tradeTarget' => __('target village'),
        ];
    }

    private function resolveTradeTarget(): ?Village
    {
        if ($this->tradeTarget === null) {
            return null;
        }

        return Village::query()->find($this->tradeTarget);
    }

    /**
     * @param array<string, int> $resources
     */
    private function calculateRequiredMerchants(array $resources, int $capacityPerMerchant): int
    {
        $total = array_sum($resources);

        if ($total === 0) {
            return 0;
        }

        return (int) max(1, ceil($total / max(1, $capacityPerMerchant)));
    }

    private function calculateEta(Village $origin, Village $target, float $speedFieldsPerHour): \Illuminate\Support\Carbon
    {
        $dx = (int) $origin->x_coordinate - (int) $target->x_coordinate;
        $dy = (int) $origin->y_coordinate - (int) $target->y_coordinate;
        $distance = sqrt(($dx ** 2) + ($dy ** 2));

        $speed = max($speedFieldsPerHour, 1.0);
        $seconds = (int) max(60, ceil(($distance / $speed) * 3600));

        return now()->addSeconds($seconds);
    }
}
