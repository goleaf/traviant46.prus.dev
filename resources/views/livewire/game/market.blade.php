@php
    $summary = $this->merchantSummary;
    $resourceKeys = ['wood', 'clay', 'iron', 'crop'];
    $ownOffers = $this->ownOffers;
    $availableOffers = $this->availableOffers;
    $outgoingTrades = $this->outgoingTrades;
    $incomingTrades = $this->incomingTrades;
    $playerVillageOptions = $this->playerVillageOptions;
    $offerMerchantsNeeded = $this->offerMerchantsNeeded;
    $tradeMerchantsNeeded = $this->tradeMerchantsNeeded;
    $tradeEtaPreview = $this->tradeEtaPreview;
@endphp

<div class="space-y-10">
    <section class="rounded-3xl border border-white/10 bg-slate-900/70 p-8 shadow-[0_35px_120px_-50px_rgba(56,189,248,0.55)] backdrop-blur dark:bg-slate-950/70">
        <div class="flex flex-col gap-6 xl:flex-row xl:items-center xl:justify-between">
            <div class="space-y-2">
                <flux:heading size="xl" class="text-white">
                    {{ __('Marketplace logistics') }}
                </flux:heading>
                <flux:description class="max-w-3xl text-slate-300">
                    {{ __('Reserve merchants for player-to-player offers, dispatch ad-hoc shipments, and track travel times without bouncing back to the legacy interface.') }}
                </flux:description>
            </div>

            <div class="flex flex-col gap-2 text-right text-sm text-slate-400">
                <span class="font-mono text-sky-200">
                    {{ __('Village coordinates: (:x, :y)', ['x' => $village->x_coordinate, 'y' => $village->y_coordinate]) }}
                </span>
                <span>
                    {{ __('Marketplace Lv :market • Trade office Lv :trade', [
                        'market' => $summary['marketplace_level'] ?? 0,
                        'trade' => $summary['trade_office_level'] ?? 0,
                    ]) }}
                </span>
            </div>
        </div>

        @if ($sitterReadOnly)
            <flux:callout variant="warning" class="mt-6 space-y-2 text-sm text-slate-100">
                <div class="font-semibold text-white">
                    {{ __('Read-only sitter mode') }}
                </div>
                <p>
                    {{ __('This sitter delegation does not include the trade permission. You can review offers and shipments, but dispatching merchants is disabled.') }}
                </p>
            </flux:callout>
        @endif

        @if ($statusMessage)
            <flux:callout variant="success" class="mt-6 text-sm text-emerald-50">
                {{ $statusMessage }}
            </flux:callout>
        @endif

        @if ($errorMessage)
            <flux:callout variant="danger" class="mt-6 text-sm text-red-100">
                {{ $errorMessage }}
            </flux:callout>
        @endif

        <dl class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-2xl border border-white/10 bg-white/5 p-5 dark:bg-slate-950/60">
                <dt class="text-xs uppercase tracking-wide text-slate-400">{{ __('Idle merchants') }}</dt>
                <dd class="mt-2 flex items-baseline gap-2">
                    <span class="text-2xl font-semibold text-white">{{ $summary['available'] ?? 0 }}</span>
                    <span class="text-xs text-slate-400">
                        {{ __('of :total total', ['total' => $summary['total'] ?? 0]) }}
                    </span>
                </dd>
            </div>

            <div class="rounded-2xl border border-white/10 bg-white/5 p-5 dark:bg-slate-950/60">
                <dt class="text-xs uppercase tracking-wide text-slate-400">{{ __('Merchants in use') }}</dt>
                <dd class="mt-2 text-2xl font-semibold text-white">
                    {{ $summary['in_use'] ?? 0 }}
                </dd>
            </div>

            <div class="rounded-2xl border border-white/10 bg-white/5 p-5 dark:bg-slate-950/60">
                <dt class="text-xs uppercase tracking-wide text-slate-400">{{ __('Capacity per merchant') }}</dt>
                <dd class="mt-2 text-2xl font-semibold text-white">
                    {{ number_format($summary['capacity_per_merchant'] ?? 0) }}
                </dd>
                <p class="mt-1 text-xs text-slate-400">
                    {{ __('Includes trade office bonus and world speed modifiers.') }}
                </p>
            </div>

            <div class="rounded-2xl border border-white/10 bg-white/5 p-5 dark:bg-slate-950/60">
                <dt class="text-xs uppercase tracking-wide text-slate-400">{{ __('Travel speed') }}</dt>
                <dd class="mt-2 text-2xl font-semibold text-white">
                    {{ number_format($summary['speed'] ?? 0, 1) }}
                </dd>
                <p class="mt-1 text-xs text-slate-400">
                    {{ __('Fields per hour for outgoing shipments.') }}
                </p>
            </div>
        </dl>
    </section>

    <div class="grid gap-8 xl:grid-cols-3">
        <div class="space-y-8 xl:col-span-2">
            <section class="rounded-3xl border border-white/10 bg-slate-900/70 p-6 shadow-[0_30px_90px_-60px_rgba(129,140,248,0.55)] backdrop-blur dark:bg-slate-950/70">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="space-y-1">
                        <flux:heading size="lg" class="text-white">
                            {{ __('Post a trade offer') }}
                        </flux:heading>
                        <flux:description class="text-slate-400">
                            {{ __('Reserve merchants by moving resources into an offer. Once another player accepts, both parties’ merchants depart automatically.') }}
                        </flux:description>
                    </div>
                    <div class="rounded-2xl bg-slate-800/50 px-4 py-3 text-sm text-slate-300">
                        {{ __('Merchants reserved') }}:
                        <span class="font-semibold text-white">
                            {{ $offerMerchantsNeeded }}
                        </span>
                    </div>
                </div>

                <form wire:submit.prevent="createOffer" class="mt-6 space-y-6">
                    <div class="grid gap-6 md:grid-cols-2">
                        <div class="space-y-3">
                            <span class="text-xs uppercase tracking-wide text-slate-400">{{ __('Give') }}</span>
                            <div class="grid gap-3 sm:grid-cols-2">
                                @foreach ($resourceKeys as $resource)
                                    <flux:field label="{{ __(ucfirst($resource)) }}">
                                        <flux:input
                                            type="number"
                                            min="0"
                                            wire:model.live="offerGive.{{ $resource }}"
                                            :disabled="$sitterReadOnly"
                                        />
                                    </flux:field>
                                @endforeach
                            </div>
                        </div>
                        <div class="space-y-3">
                            <span class="text-xs uppercase tracking-wide text-slate-400">{{ __('Want') }}</span>
                            <div class="grid gap-3 sm:grid-cols-2">
                                @foreach ($resourceKeys as $resource)
                                    <flux:field label="{{ __(ucfirst($resource)) }}">
                                        <flux:input
                                            type="number"
                                            min="0"
                                            wire:model.live="offerWant.{{ $resource }}"
                                            :disabled="$sitterReadOnly"
                                        />
                                    </flux:field>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <span class="text-sm text-slate-300">
                            {{ __('Offer will reserve :count merchants until accepted.', ['count' => $offerMerchantsNeeded]) }}
                        </span>

                        <flux:button type="submit" variant="primary" color="violet" icon="plus-circle" :disabled="$sitterReadOnly" wire:loading.attr="disabled">
                            {{ __('Post offer') }}
                        </flux:button>
                    </div>
                </form>
            </section>

            <section class="rounded-3xl border border-white/10 bg-slate-900/70 p-6 shadow-[0_25px_80px_-60px_rgba(56,189,248,0.6)] backdrop-blur dark:bg-slate-950/70">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <flux:heading size="lg" class="text-white">
                        {{ __('Your active offers') }}
                    </flux:heading>
                    <span class="text-sm text-slate-300">
                        {{ __('Resources reserved here remain unavailable until an offer is cancelled or fulfilled.') }}
                    </span>
                </div>

                <div class="mt-6 space-y-3">
                    @forelse ($ownOffers as $offer)
                        @php
                            $give = collect($offer->give ?? [])->map(fn ($value) => (int) $value)->filter();
                            $want = collect($offer->want ?? [])->map(fn ($value) => (int) $value)->filter();
                        @endphp

                        <div wire:key="own-offer-{{ $offer->id }}" class="rounded-2xl border border-white/10 bg-white/5 p-5 text-sm text-slate-200 dark:bg-slate-950/60">
                            <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs uppercase tracking-wide text-slate-400">{{ __('Give') }}</span>
                                        <span class="text-sm font-semibold text-white">
                                            {{ $give->map(fn ($amount, $label) => number_format($amount).' '.__(ucfirst($label)))->implode(', ') ?: '—' }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs uppercase tracking-wide text-slate-400">{{ __('Want') }}</span>
                                        <span class="text-sm font-semibold text-white">
                                            {{ $want->map(fn ($amount, $label) => number_format($amount).' '.__(ucfirst($label)))->implode(', ') ?: '—' }}
                                        </span>
                                    </div>
                                </div>

                                <div class="flex flex-col items-start gap-3 sm:flex-row sm:items-center">
                                    <flux:badge variant="subtle" color="sky">
                                        {{ trans_choice(':count merchant|:count merchants', (int) ($offer->merchants ?? 0), ['count' => (int) ($offer->merchants ?? 0)]) }}
                                    </flux:badge>
                                    <flux:button
                                        type="button"
                                        size="sm"
                                        variant="ghost"
                                        icon="x-mark"
                                        wire:click="cancelOffer({{ $offer->id }})"
                                        wire:loading.attr="disabled"
                                        :disabled="$sitterReadOnly"
                                    >
                                        {{ __('Cancel offer') }}
                                    </flux:button>
                                </div>
                            </div>

                            <div class="mt-3 text-xs text-slate-400">
                                {{ __('Posted :when', ['when' => optional($offer->created_at)->diffForHumans()]) }}
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-white/10 bg-white/5 p-6 text-center text-xs uppercase tracking-wide text-slate-400 dark:bg-slate-950/60">
                            {{ __('No active offers. Reserve merchants above to list a trade.') }}
                        </div>
                    @endforelse
                </div>
            </section>

            <section class="rounded-3xl border border-white/10 bg-slate-900/70 p-6 shadow-[0_25px_80px_-60px_rgba(14,165,233,0.5)] backdrop-blur dark:bg-slate-950/70">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <flux:heading size="lg" class="text-white">
                        {{ __('Nearby offers to accept') }}
                    </flux:heading>
                    <span class="text-xs uppercase tracking-wide text-slate-400">
                        {{ __('Shows the twelve most recent offers across this world.') }}
                    </span>
                </div>

                <div class="mt-6 space-y-3">
                    @forelse ($availableOffers as $offer)
                        @php
                            $give = collect($offer->give ?? [])->map(fn ($value) => (int) $value)->filter();
                            $want = collect($offer->want ?? [])->map(fn ($value) => (int) $value)->filter();
                        @endphp

                        <div wire:key="market-offer-{{ $offer->id }}" class="rounded-2xl border border-white/10 bg-white/5 p-5 dark:bg-slate-950/60">
                            <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
                                <div>
                                    <div class="text-sm font-semibold text-white">
                                        {{ $offer->village?->name ?? __('Unknown village') }}
                                    </div>
                                    <div class="text-xs text-slate-400">
                                        {{ __('Owner: :owner • Merchants: :count', [
                                            'owner' => $offer->village?->owner?->username ?? __('Unknown'),
                                            'count' => $offer->merchants ?? 0,
                                        ]) }}
                                    </div>
                                </div>
                                <div class="flex flex-col items-start gap-3 sm:flex-row sm:items-center">
                                    <div class="rounded-xl bg-slate-800/60 px-3 py-2 text-xs text-slate-300">
                                        <span class="text-slate-400">{{ __('Give') }}</span>
                                        <span class="ml-2 font-semibold text-white">
                                            {{ $give->map(fn ($amount, $label) => number_format($amount).' '.__(ucfirst($label)))->implode(', ') ?: '—' }}
                                        </span>
                                    </div>
                                    <div class="rounded-xl bg-slate-800/60 px-3 py-2 text-xs text-slate-300">
                                        <span class="text-slate-400">{{ __('Want') }}</span>
                                        <span class="ml-2 font-semibold text-white">
                                            {{ $want->map(fn ($amount, $label) => number_format($amount).' '.__(ucfirst($label)))->implode(', ') ?: '—' }}
                                        </span>
                                    </div>
                                    <flux:button
                                        type="button"
                                        size="sm"
                                        variant="primary"
                                        color="sky"
                                        icon="arrow-down-circle"
                                        wire:click="acceptOffer({{ $offer->id }})"
                                        wire:loading.attr="disabled"
                                        :disabled="$sitterReadOnly"
                                    >
                                        {{ __('Accept') }}
                                    </flux:button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-white/10 bg-white/5 p-6 text-center text-xs uppercase tracking-wide text-slate-400 dark:bg-slate-950/60">
                            {{ __('No public offers are currently available.') }}
                        </div>
                    @endforelse
                </div>
            </section>
        </div>

        <div class="space-y-8">
            <section class="rounded-3xl border border-white/10 bg-slate-900/70 p-6 shadow-[0_25px_70px_-60px_rgba(59,130,246,0.6)] backdrop-blur dark:bg-slate-950/70">
                <flux:heading size="lg" class="text-white">
                    {{ __('Send direct resources') }}
                </flux:heading>
                <flux:description class="mt-2 text-slate-400">
                    {{ __('Dispatch a one-off shipment to another village. Merchants leave immediately and arrive based on distance and tribe speed.') }}
                </flux:description>

                {{-- Merchant stats help players understand what the numbers below represent. --}}
                <p class="mt-3 text-xs text-slate-400">
                    {{ __('Each merchant can haul :capacity resources and travels at :speed fields per hour.', [
                        'capacity' => number_format($summary['capacity_per_merchant'] ?? 0),
                        'speed' => number_format($summary['speed'] ?? 0, 1),
                    ]) }}
                </p>

                <form wire:submit.prevent="sendTrade" class="mt-6 space-y-6">
                    <div class="space-y-3">
                        <span class="text-xs uppercase tracking-wide text-slate-400">{{ __('Payload') }}</span>
                        <div class="grid gap-3 sm:grid-cols-2">
                            @foreach ($resourceKeys as $resource)
                                <flux:field label="{{ __(ucfirst($resource)) }}">
                                    <flux:input
                                        type="number"
                                        min="0"
                                        wire:model.live="tradePayload.{{ $resource }}"
                                        :disabled="$sitterReadOnly"
                                    />
                                </flux:field>
                            @endforeach
                        </div>
                    </div>

                    <flux:field label="{{ __('Destination village') }}" hint="{{ __('Only your own villages are listed for now.') }}">
                        <flux:select wire:model.live="tradeTarget" :disabled="$sitterReadOnly">
                            <option value="">{{ __('Select a village') }}</option>
                            @foreach ($playerVillageOptions as $targetVillage)
                                <option value="{{ $targetVillage->id }}">
                                    {{ $targetVillage->name }} ({{ $targetVillage->x_coordinate }}, {{ $targetVillage->y_coordinate }})
                                </option>
                            @endforeach
                        </flux:select>
                    </flux:field>

                    <div class="space-y-2 rounded-2xl border border-white/10 bg-slate-800/50 px-4 py-3 text-sm text-slate-200">
                        <div class="flex items-center justify-between">
                            <span>{{ __('Merchants required') }}</span>
                            <span class="font-semibold text-white">
                                {{ $tradeMerchantsNeeded }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-xs text-slate-400">
                            <span>{{ __('Estimated arrival') }}</span>
                            <span class="font-mono text-sky-200">
                                {{ $tradeEtaPreview ?? __('Enter payload for ETA') }}
                            </span>
                        </div>
                    </div>

                    <flux:button type="submit" variant="primary" icon="paper-airplane" class="w-full" :disabled="$sitterReadOnly" wire:loading.attr="disabled">
                        {{ __('Send trade') }}
                    </flux:button>
                </form>
            </section>

            <section class="rounded-3xl border border-white/10 bg-slate-900/70 p-6 shadow-[0_25px_70px_-60px_rgba(14,165,233,0.45)] backdrop-blur dark:bg-slate-950/70">
                <flux:heading size="lg" class="text-white">
                    {{ __('Outgoing shipments') }}
                </flux:heading>

                <ul class="mt-4 space-y-3">
                    @forelse ($outgoingTrades as $trade)
                        @php
                            $resources = collect(data_get($trade->payload, 'resources', []))
                                ->map(fn ($value) => (int) $value)
                                ->filter();
                        @endphp
                        <li wire:key="outgoing-trade-{{ $trade->id }}" class="rounded-2xl border border-white/10 bg-white/5 p-4 text-sm text-slate-200 dark:bg-slate-950/60">
                            <div class="flex flex-col gap-2">
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold text-white">
                                        {{ __('To :name', ['name' => $trade->targetVillage?->name ?? __('Unknown')]) }}
                                    </span>
                                    <flux:badge variant="subtle" color="emerald">
                                        {{ trans_choice(':count merchant|:count merchants', (int) data_get($trade->payload, 'merchants', 0), ['count' => (int) data_get($trade->payload, 'merchants', 0)]) }}
                                    </flux:badge>
                                </div>
                                <div class="text-xs text-slate-400">
                                    {{ $resources->map(fn ($amount, $label) => number_format($amount).' '.__(ucfirst($label)))->implode(', ') ?: __('No payload recorded') }}
                                </div>
                                <div class="flex items-center justify-between text-xs text-slate-400">
                                    <span>{{ __('ETA: :time', ['time' => optional($trade->eta)->diffForHumans()]) }}</span>
                                    <span>{{ __('Context: :context', ['context' => data_get($trade->payload, 'context', 'direct')]) }}</span>
                                </div>
                            </div>
                        </li>
                    @empty
                        <li class="rounded-2xl border border-dashed border-white/10 bg-white/5 p-5 text-center text-xs uppercase tracking-wide text-slate-400 dark:bg-slate-950/60">
                            {{ __('No merchants are currently en route from this village.') }}
                        </li>
                    @endforelse
                </ul>
            </section>

            <section class="rounded-3xl border border-white/10 bg-slate-900/70 p-6 shadow-[0_25px_70px_-60px_rgba(126,34,206,0.45)] backdrop-blur dark:bg-slate-950/70">
                <flux:heading size="lg" class="text-white">
                    {{ __('Incoming shipments') }}
                </flux:heading>

                <ul class="mt-4 space-y-3">
                    @forelse ($incomingTrades as $trade)
                        @php
                            $resources = collect(data_get($trade->payload, 'resources', []))
                                ->map(fn ($value) => (int) $value)
                                ->filter();
                        @endphp
                        <li wire:key="incoming-trade-{{ $trade->id }}" class="rounded-2xl border border-white/10 bg-white/5 p-4 text-sm text-slate-200 dark:bg-slate-950/60">
                            <div class="flex flex-col gap-2">
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold text-white">
                                        {{ __('From :name', ['name' => $trade->originVillage?->name ?? __('Unknown')]) }}
                                    </span>
                                    <flux:badge variant="subtle" color="violet">
                                        {{ trans_choice(':count merchant|:count merchants', (int) data_get($trade->payload, 'merchants', 0), ['count' => (int) data_get($trade->payload, 'merchants', 0)]) }}
                                    </flux:badge>
                                </div>
                                <div class="text-xs text-slate-400">
                                    {{ $resources->map(fn ($amount, $label) => number_format($amount).' '.__(ucfirst($label)))->implode(', ') ?: __('No payload recorded') }}
                                </div>
                                <div class="flex items-center justify-between text-xs text-slate-400">
                                    <span>{{ __('ETA: :time', ['time' => optional($trade->eta)->diffForHumans()]) }}</span>
                                    <span>{{ __('Context: :context', ['context' => data_get($trade->payload, 'context', 'direct')]) }}</span>
                                </div>
                            </div>
                        </li>
                    @empty
                        <li class="rounded-2xl border border-dashed border-white/10 bg-white/5 p-5 text-center text-xs uppercase tracking-wide text-slate-400 dark:bg-slate-950/60">
                            {{ __('No merchants are currently heading toward this village.') }}
                        </li>
                    @endforelse
                </ul>
            </section>
        </div>
    </div>
</div>
