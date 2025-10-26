<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use function Livewire\Volt\{mount, state};

state([
    'checkout' => [],
    'currency' => 'USD',
]);

mount(function (array $checkout, ?string $currency = null): void {
    $this->checkout = $checkout;
    $this->currency = $currency ?? $checkout['cart']['currency'] ?? config('storefront.currency', 'USD');
});
?>

<section class="bg-slate-950 py-16 text-white">
    <div class="mx-auto flex max-w-5xl flex-col gap-12 px-6">
        <header class="space-y-3 text-center md:text-left">
            <h1 class="text-3xl font-semibold sm:text-4xl">
                {{ __('storefront.checkout.headline') }}
            </h1>
            <p class="text-slate-300">
                {{ __('storefront.checkout.read_only') }}
            </p>
        </header>

        <div class="grid gap-10 lg:grid-cols-[2fr_1fr]">
            <div class="space-y-8">
                <section class="rounded-3xl border border-slate-800 bg-slate-900/70 p-6">
                    <h2 class="text-lg font-semibold text-white">
                        {{ __('storefront.checkout.steps_title') }}
                    </h2>
                    <ol class="mt-6 space-y-4">
                        @foreach ($checkout['steps'] as $index => $step)
                            @php
                                $stepTitle = __('storefront.checkout.steps.' . $step . '.title');
                                $stepDescription = __('storefront.checkout.steps.' . $step . '.description');
                                if ($stepTitle === 'storefront.checkout.steps.' . $step . '.title') {
                                    $stepTitle = Str::headline($step);
                                }
                                if ($stepDescription === 'storefront.checkout.steps.' . $step . '.description') {
                                    $stepDescription = '';
                                }
                            @endphp
                            <li class="flex gap-4">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-amber-400/10 text-amber-300">
                                    {{ $index + 1 }}
                                </div>
                                <div class="space-y-1">
                                    <h3 class="text-base font-semibold text-white">
                                        {{ $stepTitle }}
                                    </h3>
                                    @if ($stepDescription !== '')
                                        <p class="text-sm text-slate-300">
                                            {{ $stepDescription }}
                                        </p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                    @if (! empty($checkout['next_release']))
                        <p class="mt-6 text-sm text-slate-400">
                            {{ __($checkout['next_release']) }}
                        </p>
                    @endif
                </section>

                <section class="rounded-3xl border border-slate-800 bg-slate-900/70 p-6">
                    <h2 class="text-lg font-semibold text-white">
                        {{ __('storefront.cart.summary') }}
                    </h2>
                    <dl class="mt-6 space-y-3 text-sm text-slate-300">
                        <div class="flex items-center justify-between">
                            <dt>{{ __('storefront.cart.subtotal') }}</dt>
                            <dd>{{ __('storefront.common.currency_suffix', ['value' => number_format($checkout['cart']['summary']['subtotal'], 2), 'currency' => $currency]) }}</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt>{{ __('storefront.cart.taxes') }}</dt>
                            <dd>{{ __('storefront.common.currency_suffix', ['value' => number_format($checkout['cart']['summary']['tax_amount'], 2), 'currency' => $currency]) }}</dd>
                        </div>
                        <div class="flex items-center justify-between text-base font-semibold text-white">
                            <dt>{{ __('storefront.cart.total') }}</dt>
                            <dd>{{ __('storefront.common.currency_suffix', ['value' => number_format($checkout['cart']['summary']['total'], 2), 'currency' => $currency]) }}</dd>
                        </div>
                    </dl>
                    <p class="mt-4 text-xs text-slate-400">
                        {{ __('storefront.cart.tax_hint') }}
                    </p>
                </section>
            </div>

            <aside class="space-y-6 rounded-3xl border border-slate-800 bg-slate-900/60 p-6">
                <h2 class="text-lg font-semibold text-white">
                    {{ __('storefront.checkout.next_step') }}
                </h2>
                <p class="text-sm text-slate-300">
                    {{ __('storefront.checkout.read_only') }}
                </p>
                <a class="inline-flex items-center justify-center rounded-full bg-white/10 px-4 py-2 text-sm font-medium text-white transition hover:bg-white/20" href="{{ route('storefront.cart') }}">
                    {{ __('storefront.checkout.cta') }}
                </a>
            </aside>
        </div>
    </div>
</section>
