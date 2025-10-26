@extends('layouts.app')

{{-- Placeholder cart page to confirm routing while cart calculations are implemented. --}}
@php
    $cartTitle = __('storefront.cart.title');
    if ($cartTitle === 'storefront.cart.title') {
        $cartTitle = 'Storefront Cart';
    }

    $cartHeading = __('storefront.cart.heading');
    if ($cartHeading === 'storefront.cart.heading') {
        $cartHeading = 'Your Cart is Almost Ready';
    }

    $cartDescription = __('storefront.cart.description');
    if ($cartDescription === 'storefront.cart.description') {
        $cartDescription = 'Cart totals and payment integration will appear here soon.';
    }

    $cartCta = __('storefront.cart.checkout_cta');
    if ($cartCta === 'storefront.cart.checkout_cta') {
        $cartCta = 'Preview Checkout';
    }
@endphp

@section('title', $cartTitle)

@section('content')
    <section class="bg-slate-950 py-16 text-white">
        <div class="mx-auto flex max-w-3xl flex-col gap-6 px-6 text-center">
            <h1 class="text-3xl font-semibold">
                {{ $cartHeading }}
            </h1>
            <p class="text-slate-300">
                {{ $cartDescription }}
            </p>
            <a
                class="mx-auto inline-flex items-center justify-center rounded-full bg-white/10 px-5 py-2 text-sm font-medium text-white transition hover:bg-white/20"
                href="{{ route('storefront.checkout') }}"
            >
                {{ $cartCta }}
            </a>
        </div>
    </section>
@endsection
