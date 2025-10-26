@php
    $labels = [
        'inbox' => __('Inbox'),
        'sent' => __('Sent'),
        'compose' => __('Compose'),
    ];
@endphp

<div class="space-y-8">
    <div class="rounded-3xl border border-white/10 bg-slate-900/70 p-8 shadow-[0_35px_120px_-60px_rgba(15,23,42,0.85)] backdrop-blur">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div class="space-y-2">
                <flux:heading size="lg" class="text-white">
                    {{ __('Messages') }}
                </flux:heading>
                <flux:description class="text-slate-400">
                    {{ __('Keep in touch with neighbouring rulers, coordinate attacks, and stay on top of alliance chatter.') }}
                </flux:description>
            </div>
        </div>

        @if ($flashMessage)
            <flux:callout variant="success" icon="check-circle" class="mt-6 border-emerald-500/30 bg-emerald-900/30 text-emerald-50">
                <div class="flex items-center justify-between gap-4">
                    <span>{{ $flashMessage }}</span>
                    <flux:button wire:click="dismissFlash" size="xs" variant="ghost" class="text-emerald-100 hover:text-white">
                        {{ __('Dismiss') }}
                    </flux:button>
                </div>
            </flux:callout>
        @endif

        <div class="mt-6 flex flex-wrap gap-3">
            @foreach ($tabs as $tab)
                @php($isActive = $currentTab === $tab)
                <button
                    type="button"
                    wire:click="switchTab('{{ $tab }}')"
                    wire:loading.attr="disabled"
                    class="{{ $isActive ? 'bg-sky-500/20 text-sky-200 ring-1 ring-sky-400/60' : 'bg-slate-800/60 text-slate-300 hover:bg-slate-700/80' }} rounded-full px-5 py-2 text-sm font-medium transition-all"
                >
                    {{ $labels[$tab] ?? ucfirst($tab) }}
                </button>
            @endforeach
        </div>
    </div>

    <div>
        @if ($currentTab === 'inbox')
            <livewire:messages.inbox wire:key="messages-inbox" />
        @elseif ($currentTab === 'sent')
            <livewire:messages.sent wire:key="messages-sent" />
        @else
            <livewire:messages.compose wire:key="messages-compose" />
        @endif
    </div>
</div>
