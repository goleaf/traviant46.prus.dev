<?php

use Illuminate\Support\Str;
?>

<section class="bg-slate-950 py-16 text-white">
    <div class="mx-auto flex max-w-5xl flex-col gap-12 px-6">
        <div class="space-y-4">
            <a class="text-sm font-medium text-amber-300 hover:text-amber-200" href="{{ route('storefront.catalogue') }}">
                ← {{ __('storefront.product.cta') }}
            </a>
            <p class="text-sm uppercase tracking-widest text-slate-400">
                {{ __('storefront.product.headline_prefix') }}
            </p>
            <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div class="space-y-3">
                    <h1 class="text-3xl font-semibold sm:text-4xl">
                        {{ $productName }}
                    </h1>
                    <p class="text-lg text-slate-300">
                        {{ $productSummary }}
                    </p>
                </div>
                <div class="flex flex-col items-start gap-2 text-left md:items-end">
                    <div class="text-3xl font-semibold text-amber-300">
                        {{ __('storefront.common.currency_suffix', ['value' => number_format($product['price'], 2), 'currency' => $currency]) }}
                    </div>
                    <div class="text-xs uppercase tracking-wide text-slate-400">
                        {{ __('storefront.common.per_item') }}
                    </div>
                </div>
            </div>
        </div>

        <div class="grid gap-8 lg:grid-cols-[2fr_1fr]">
            <article class="space-y-6 rounded-3xl border border-slate-800 bg-slate-900/70 p-6 shadow-lg shadow-slate-900/50">
                <h2 class="text-xl font-semibold text-white">
                    {{ __('storefront.product.includes') }}
                </h2>
                <ul class="grid gap-3 sm:grid-cols-2">
                    @foreach ($product['features'] ?? [] as $feature)
                        <li class="flex items-start gap-3 rounded-2xl border border-slate-800 bg-slate-950/60 p-4">
                            <span class="mt-1 inline-flex h-2.5 w-2.5 shrink-0 rounded-full bg-emerald-400"></span>
                            <span class="text-sm text-slate-200">
                                {{ __('storefront.features.' . $feature) }}
                            </span>
                        </li>
                    @endforeach
                </ul>

        
                @if ($productDescription !== '')
                    <div class="space-y-3">
                        <h3 class="text-lg font-semibold text-white">
                            {{ __('storefront.product.details_heading') }}
                        </h3>
                        <p class="text-slate-300">
                            {{ $productDescription }}
                        </p>
                    </div>
                @endif
            </article>

            <aside class="flex flex-col gap-6">
                <div class="rounded-3xl border border-slate-800 bg-slate-900/60 p-6">
                    <h2 class="text-sm font-semibold uppercase tracking-widest text-slate-300">
                        {{ __('storefront.product.availability_title') }}
                    </h2>
                    <p class="mt-3 text-base text-white">
                        {{ __('storefront.product.availability.' . ($product['availability'] ?? 'in_stock')) }}
                    </p>
                    <p class="mt-4 text-sm text-slate-300">
                        {{ __('storefront.product.delivery.' . ($product['delivery'] ?? 'instant')) }}
                    </p>
                </div>

                @if ($relatedProducts !== [])
                    <div class="rounded-3xl border border-slate-800 bg-slate-900/60 p-6">
                        <h2 class="text-sm font-semibold uppercase tracking-widest text-slate-300">
                            {{ __('storefront.product.related') }}
                        </h2>
                        <ul class="mt-4 flex flex-col gap-4">
                            @foreach ($relatedProducts as $related)
                                @php
                                    $relatedName = __('storefront.products.' . $related['slug'] . '.name');
                                    if ($relatedName === 'storefront.products.' . $related['slug'] . '.name') {
                                        $relatedName = $related['name'] ?? Str::headline($related['slug']);
                                    }
                                @endphp
                                <li class="flex flex-col gap-2 rounded-2xl border border-slate-800 bg-slate-950/70 p-4">
                                    <div class="text-sm font-semibold text-white">
                                        {{ $relatedName }}
                                    </div>
                                    <div class="text-xs text-slate-400">
                                        {{ __('storefront.common.currency_suffix', ['value' => number_format($related['price'], 2), 'currency' => $currency]) }}
                                    </div>
                                    <a
                                        class="inline-flex items-center gap-2 text-sm font-medium text-amber-300 hover:text-amber-200"
                                        href="{{ route('storefront.products.show', $related['slug']) }}"
                                    >
                                        {{ __('storefront.catalogue.cta') }}
                                        <span aria-hidden="true">→</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </aside>
        </div>
    </div>
</section>
