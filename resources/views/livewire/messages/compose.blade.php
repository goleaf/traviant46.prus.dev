@php($hasAddresses = ! empty($addressBook))

<div class="rounded-3xl border border-white/10 bg-slate-900/70 p-8 shadow-[0_35px_120px_-60px_rgba(15,23,42,0.85)] backdrop-blur">
    <div class="space-y-2">
        <flux:heading size="md" class="text-white">
            {{ __('Compose message') }}
        </flux:heading>
        <flux:description class="text-slate-400">
            {{ __('Write a text-only mail to another ruler. Messages deliver instantly unless the player is banned.') }}
        </flux:description>
    </div>

    <flux:separator class="my-6" />

    @if ($statusMessage)
        <flux:callout variant="success" icon="check-circle" class="border-emerald-500/30 bg-emerald-900/30 text-emerald-50">
            {{ $statusMessage }}
        </flux:callout>
    @endif

    @if ($errorMessage)
        <flux:callout variant="error" icon="no-symbol" class="border-red-500/40 bg-red-900/30 text-red-100">
            {{ $errorMessage }}
        </flux:callout>
    @endif

    <form wire:submit.prevent="send" class="mt-6 space-y-6">
        <div class="grid gap-5 md:grid-cols-2">
            <flux:field label="{{ __('Recipient username') }}" hint="{{ __('Enter the commander you wish to reach.') }}">
                <flux:input
                    wire:model.live="recipient"
                    maxlength="50"
                    placeholder="{{ __('e.g. Skylord') }}"
                />
            </flux:field>
            <flux:field label="{{ __('Subject') }}" hint="{{ __('Summarise your intent in a short phrase.') }}">
                <flux:input
                    wire:model.live="subject"
                    maxlength="120"
                    placeholder="{{ __('War council at sunrise') }}"
                />
            </flux:field>
        </div>

        @error('recipient')
            <p class="text-sm text-red-400">{{ $message }}</p>
        @enderror
        @error('subject')
            <p class="text-sm text-red-400">{{ $message }}</p>
        @enderror

        <flux:field label="{{ __('Message body') }}" hint="{{ __('Markdown and rich formatting are disabled for now.') }}">
            <textarea
                wire:model.live="body"
                rows="8"
                maxlength="4000"
                class="w-full rounded-2xl border border-white/10 bg-slate-950/70 p-4 text-sm text-slate-100 outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-500/60"
                placeholder="{{ __('Keep it respectful. Harassment leads to penalties.') }}"
            ></textarea>
        </flux:field>
        @error('body')
            <p class="text-sm text-red-400">{{ $message }}</p>
        @enderror

        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <p class="text-sm text-slate-400">
                {{ __('Messages are private unless forwarded. Respect sitter permissions and alliance agreements.') }}
            </p>
            <flux:button type="submit" icon="paper-airplane" class="w-full md:w-auto">
                {{ __('Send message') }}
            </flux:button>
        </div>
    </form>

    <flux:separator class="my-6" />

    <div class="space-y-3">
        <flux:heading size="sm" class="text-white">
            {{ __('Recent contacts') }}
        </flux:heading>

        @if (! $hasAddresses)
            <flux:callout variant="subtle" icon="users" class="text-slate-200">
                {{ __('Your address book is empty. Once you message others, shortcuts will appear here.') }}
            </flux:callout>
        @else
            <div class="flex flex-wrap gap-2">
                @foreach ($addressBook as $contact)
                    <flux:button
                        wire:key="address-book-{{ $contact['id'] }}"
                        size="sm"
                        variant="outline"
                        wire:click="$set('recipient', '{{ $contact['username'] }}')"
                    >
                        {{ $contact['username'] }}
                        @if (! empty($contact['name']))
                            <span class="ml-2 text-xs text-slate-400">({{ $contact['name'] }})</span>
                        @endif
                    </flux:button>
                @endforeach
            </div>
        @endif
    </div>
</div>
