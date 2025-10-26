@php
    /** @var \Illuminate\Contracts\Pagination\LengthAwarePaginator $messages */
    $rows = $messages->items();
@endphp

<div class="rounded-3xl border border-white/10 bg-slate-900/70 p-8 shadow-[0_35px_120px_-60px_rgba(15,23,42,0.85)] backdrop-blur">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div class="space-y-1">
            <flux:heading size="md" class="text-white">
                {{ __('Sent mail') }}
            </flux:heading>
            <flux:description class="text-slate-400">
                {{ __('Review the letters you have dispatched to other rulers.') }}
            </flux:description>
        </div>
        <span class="text-xs uppercase tracking-wide text-slate-500">
            {{ trans_choice(':count message|:count messages', $messages->total()) }}
        </span>
    </div>

    <flux:separator class="my-6" />

    @if (empty($rows))
        <flux:callout variant="subtle" icon="inbox-arrow-down" class="text-slate-200">
            {{ __('You have not sent any messages yet. Compose a note to get a conversation going.') }}
        </flux:callout>
    @else
        <div class="divide-y divide-white/5">
            @foreach ($messages as $message)
                @php
                    $visibleRecipients = $message->recipients->filter(fn ($recipient) => $recipient->deleted_at === null);
                    $recipientNames = $visibleRecipients->map(fn ($recipient) => $recipient->recipient?->username ?? __('Unknown'))->all();
                    $listPreview = implode(', ', array_slice($recipientNames, 0, 3));
                    $additional = max(count($recipientNames) - 3, 0);
                    $sentAt = optional($message->sent_at)->diffForHumans(null, true) ?? __('unsent');
                    $preview = \Illuminate\Support\Str::limit(strip_tags((string) $message->body), 160);
                @endphp

                <article wire:key="sent-message-{{ $message->getKey() }}" class="flex flex-col gap-3 bg-slate-950/40 p-5 transition-colors hover:bg-slate-900/60">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div class="space-y-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <flux:badge variant="solid" color="sky">
                                    {{ __('Sent') }}
                                </flux:badge>
                                <span class="text-sm font-medium text-white">{{ $message->subject ?: __('(No subject)') }}</span>
                            </div>
                            <p class="text-xs uppercase tracking-wide text-slate-400">
                                {{ __('To: :recipients', ['recipients' => $listPreview ?: __('Unknown recipient')]) }}
                                @if ($additional > 0)
                                    <span class="text-slate-500">+{{ $additional }}</span>
                                @endif
                            </p>
                        </div>

                        <span class="text-xs uppercase tracking-wide text-slate-400">
                            {{ __('Sent :time ago', ['time' => $sentAt]) }}
                        </span>
                    </div>

                    <p class="text-sm leading-relaxed text-slate-300">
                        {{ $preview }}
                    </p>
                </article>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $messages->links() }}
        </div>
    @endif
</div>
