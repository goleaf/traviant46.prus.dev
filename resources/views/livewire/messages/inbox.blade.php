@php
    /** @var \Illuminate\Contracts\Pagination\LengthAwarePaginator $messages */
    $entries = $messages->items();
    $canBulk = data_get($capabilities, 'bulk', false);
    $canArchive = data_get($capabilities, 'archive', false);
    $selectedIds = $selected ?? [];
    $hasSelection = ! empty($selectedIds);
@endphp

<div class="rounded-3xl border border-white/10 bg-slate-900/70 p-8 shadow-[0_35px_120px_-60px_rgba(15,23,42,0.85)] backdrop-blur">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div class="space-y-2">
            <flux:heading size="md" class="text-white">
                {{ __('Inbox') }}
            </flux:heading>
            <flux:description class="text-slate-400">
                {{ __('All private correspondence from other rulers and system dispatches lands here.') }}
            </flux:description>
        </div>

        <label class="flex items-center gap-2 text-sm text-slate-300">
            <input
                type="checkbox"
                wire:model.live="recursive"
                class="size-4 rounded border-white/10 bg-slate-950 text-sky-400 focus:ring-sky-500"
            >
            <span>{{ __('Include alliance broadcasts') }}</span>
        </label>
    </div>

    <flux:separator class="my-6" />

    @if ($spamWarning)
        <flux:callout variant="warning" icon="shield-exclamation" class="border-amber-500/40 bg-amber-900/30 text-amber-100">
            {{ $spamWarning }}
        </flux:callout>
    @endif

    <div class="mt-6 flex flex-wrap items-center gap-3">
        <flux:button
            size="sm"
            variant="ghost"
            icon="cursor-arrow-rays"
            wire:click="selectVisible"
            class="text-slate-200 hover:text-white"
        >
            {{ __('Select page') }}
        </flux:button>
        <flux:button
            size="sm"
            variant="ghost"
            icon="x-mark"
            wire:click="resetSelection"
            class="text-slate-400 hover:text-slate-100"
        >
            {{ __('Clear selection') }}
        </flux:button>

        <div class="ml-auto flex flex-wrap items-center gap-2">
            <flux:button
                size="sm"
                icon="envelope-open"
                wire:click="bulkMarkAsRead"
                :disabled="!($canBulk && $hasSelection)"
                class="{{ $canBulk && $hasSelection ? '' : 'pointer-events-none opacity-50' }}"
            >
                {{ __('Mark read') }}
            </flux:button>
            <flux:button
                size="sm"
                icon="envelope"
                wire:click="bulkMarkAsUnread"
                :disabled="!($canBulk && $hasSelection)"
                class="{{ $canBulk && $hasSelection ? '' : 'pointer-events-none opacity-50' }}"
            >
                {{ __('Mark unread') }}
            </flux:button>

            @if ($canArchive)
                <flux:button
                    size="sm"
                    variant="outline"
                    icon="archive-box"
                    wire:click="bulkArchive"
                    :disabled="!$hasSelection"
                    class="{{ $hasSelection ? '' : 'pointer-events-none opacity-50' }}"
                >
                    {{ __('Archive') }}
                </flux:button>
                <flux:button
                    size="sm"
                    variant="ghost"
                    icon="trash"
                    wire:click="bulkDelete"
                    :disabled="!$hasSelection"
                    class="{{ $hasSelection ? 'text-red-300 hover:text-red-100' : 'pointer-events-none opacity-50' }}"
                >
                    {{ __('Delete') }}
                </flux:button>
            @endif
        </div>
    </div>

    <div class="mt-6">
        @if (empty($entries))
            <flux:callout variant="subtle" icon="inbox" class="text-slate-200">
                {{ __('No messages in your inbox yet. Rally your neighbours or await incoming reports.') }}
            </flux:callout>
        @else
            <div class="rounded-2xl border border-white/10">
                <ul class="divide-y divide-white/10">
                    @foreach ($messages as $recipient)
                        @php
                            /** @var \App\Models\MessageRecipient $recipient */
                            $message = $recipient->message;
                            $sender = $message?->sender;
                            $isUnread = $recipient->status === 'unread';
                            $sentAt = optional($message?->sent_at)->diffForHumans(null, true) ?? __('pending');
                            $preview = \Illuminate\Support\Str::limit(strip_tags((string) ($message?->body ?? '')), 160);
                            $checked = in_array($recipient->id, $selectedIds, true);
                        @endphp

                        <li wire:key="inbox-message-{{ $recipient->id }}" class="bg-slate-950/40 px-4 py-5 transition-colors hover:bg-slate-900/60">
                            <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                                <div class="flex flex-1 items-start gap-4">
                                    <input
                                        type="checkbox"
                                        wire:click="toggleSelection({{ $recipient->id }})"
                                        @checked($checked)
                                        class="mt-1 size-4 rounded border-white/10 bg-slate-950 text-sky-400 focus:ring-sky-500"
                                    >

                                    <div class="space-y-2">
                                        <div class="flex flex-wrap items-center gap-2">
                                            @if ($isUnread)
                                                <flux:badge variant="solid" color="sky">
                                                    {{ __('Unread') }}
                                                </flux:badge>
                                            @else
                                                <flux:badge variant="solid" color="slate">
                                                    {{ __('Read') }}
                                                </flux:badge>
                                            @endif

                                            <span class="text-sm font-semibold text-white">
                                                {{ $message?->subject ?: __('(No subject)') }}
                                            </span>
                                        </div>

                                        <div class="flex flex-wrap items-center gap-3 text-xs uppercase tracking-wide text-slate-400">
                                            <span>{{ __('From: :sender', ['sender' => $sender?->username ?? __('System')]) }}</span>
                                            <span class="text-slate-500">•</span>
                                            <span>{{ __('Sent :time ago', ['time' => $sentAt]) }}</span>
                                        </div>

                                        <p class="text-sm leading-relaxed text-slate-300">
                                            {{ $preview }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex flex-col gap-2">
                                    <flux:button
                                        size="sm"
                                        variant="outline"
                                        icon="{{ $isUnread ? 'envelope-open' : 'envelope' }}"
                                        wire:click="toggleRead({{ $recipient->id }})"
                                        class="justify-start"
                                    >
                                        {{ $isUnread ? __('Mark read') : __('Mark unread') }}
                                    </flux:button>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="mt-6">
                {{ $messages->links() }}
            </div>
        @endif
    </div>
</div>
