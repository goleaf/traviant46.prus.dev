@extends('layouts.app')

@section('title', 'Catalogue | Travian Storefront')

@section('content')
    <section class="bg-slate-950 py-16 text-white">
        <div class="mx-auto flex max-w-5xl flex-col gap-8 px-6">
            <header class="space-y-3 text-center md:text-left">
                <h1 class="text-3xl font-semibold sm:text-4xl">
                    Storefront Catalogue
                </h1>
                <p class="text-base text-slate-300">
                    Browse upcoming Travian bundles and premium upgrades.
                </p>
            </header>

            <div class="grid gap-6 sm:grid-cols-2">
                <article class="space-y-3 rounded-3xl border border-slate-800 bg-slate-900/70 p-6 text-left">
                    <h2 class="text-xl font-semibold text-white">
                        Coming Soon
                    </h2>
                    <p class="text-sm text-slate-300">
                        New product drops are on the way. Check back soon for launch details.
                    </p>
                </article>
            </div>
        </div>
    </section>
@endsection
