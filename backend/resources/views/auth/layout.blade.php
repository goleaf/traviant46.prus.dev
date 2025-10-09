@extends('layouts.app')

@section('title', trim($__env->yieldContent('title', __('Authentication'))))

@section('content')
    <div class="flex flex-1 items-center justify-center px-4 py-16 sm:px-6 lg:px-8">
        <div class="w-full max-w-md">
            <div class="rounded-3xl border border-white/10 bg-white/5 px-6 py-8 shadow-2xl backdrop-blur sm:px-8">
                <div class="space-y-2 text-center">
                    <flux:heading level="1" size="xl" class="text-white">
                        @yield('title')
                    </flux:heading>

                    @hasSection('subtitle')
                        <flux:description class="text-slate-400">
                            @yield('subtitle')
                        </flux:description>
                    @endif
                </div>

                <div class="mt-6 space-y-4">
                    @if (session('status'))
                        <flux:callout variant="filled" icon="sparkles" class="text-left">
                            {{ session('status') }}
                        </flux:callout>
                    @endif

                    @if ($errors->any())
                        <flux:callout variant="danger" icon="exclamation-triangle" class="text-left">
                            <ul class="list-disc space-y-1 ps-5 text-sm">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </flux:callout>
                    @endif
                </div>

                <div class="mt-8 space-y-6">
                    @yield('content')
                </div>
            </div>
        </div>
    </div>
@endsection
