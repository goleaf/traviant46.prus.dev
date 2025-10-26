@extends('layouts.app')

{{-- Placeholder catalogue page until product listing integration is completed. --}}
@php
    $catalogueTitle = __('storefront.catalogue.title');
    if ($catalogueTitle === 'storefront.catalogue.title') {
        $catalogueTitle = 'Storefront Catalogue';
    }

    $catalogueHeading = __('storefront.catalogue.heading');
    if ($catalogueHeading === 'storefront.catalogue.heading') {
        $catalogueHeading = 'Browse the TravianT Storefront';
    }

    $catalogueDescription = __('storefront.catalogue.description');
    if ($catalogueDescription === 'storefront.catalogue.description') {
        $catalogueDescription = 'Product listings will appear here once the catalogue service is connected.';
    }

    $catalogueHint = __('storefront.catalogue.cta_hint');
    if ($catalogueHint === 'storefront.catalogue.cta_hint') {
        $catalogueHint = 'Check back soon for curated bundles and seasonal offers.';
    }
@endphp

@section('title', $catalogueTitle)

@section('content')
    <section class="bg-slate-950 py-16 text-white">
        <div class="mx-auto flex max-w-4xl flex-col gap-6 px-6 text-center">
            <h1 class="text-3xl font-semibold">
                {{ $catalogueHeading }}
            </h1>
            <p class="text-slate-300">
                {{ $catalogueDescription }}
            </p>
            <p class="text-sm text-slate-400">
                {{ $catalogueHint }}
            </p>
        </div>
    </section>
@endsection
