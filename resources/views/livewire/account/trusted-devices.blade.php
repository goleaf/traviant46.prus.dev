@php($hasDevices = ! empty($devices))

<div class="space-y-6">
    <div class="rounded-3xl border border-white/10 bg-slate-900/70 p-8 shadow-[0_30px_80px_-40px_rgba(15,23,42,0.65)] backdrop-blur">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div class="space-y-2">
                <flux:heading size="lg" class="text-white">
                    {{ __('Trusted devices') }}
                </flux:heading>
                <flux:description class="text-slate-400">
                    {{ __('Remember devices you control to streamline sensitive actions while retaining full auditability.') }}
                </flux:description>
            </div>

            @if ($managerEnabled && $hasDevices)
                <flux:button
                    wire:click="revokeOthers"
                    variant="outline"
                    icon="shield-exclamation"
                    class="w-full sm:w-auto"
                >
                    {{ __('Revoke other devices') }}
                </flux:button>
            @endif
        </div>

        <flux:separator class="my-6" />

        @if ($statusMessage)
            <flux:callout variant="success" icon="check-circle" class="border-emerald-500/40 bg-emerald-900/30 text-emerald-50">
                {{ $statusMessage }}
            </flux:callout>
        @endif

        @if (! $managerEnabled)
            <flux:callout variant="error" icon="lock-closed" class="border-red-500/40 bg-red-900/30 text-red-100">
                {{ __('Trusted devices are disabled for this environment. Contact support if you need to enable them.') }}
            </flux:callout>
        @else
            <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-6">
                <form wire:submit.prevent="rememberDevice" class="space-y-5">
                    <flux:field label="{{ __('Label (optional)') }}" hint="{{ __('Use something memorable like “Office laptop” or “Pixel 9”.') }}">
                        <flux:input
                            wire:model.defer="rememberLabel"
                            placeholder="{{ __('e.g. Home workstation') }}"
                            maxlength="120"
                        />
                    </flux:field>
                    @error('rememberLabel')
                        <p class="text-sm text-red-400">{{ $message }}</p>
                    @enderror

                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <p class="text-sm text-slate-400">
                            {{ __('Trusting this device stores a secure token in your browser so future sitter approvals and payments can skip secondary checks. You can revoke access at any time.') }}
                        </p>
                        <flux:button type="submit" icon="shield-check">
                            {{ __('Trust this device') }}
                        </flux:button>
                    </div>
                </form>
            </div>
        @endif

        <flux:separator class="my-6" />

        <div class="space-y-4">
            <flux:heading size="md" class="text-white">
                {{ __('Remembered devices') }}
            </flux:heading>

            @if (! $hasDevices)
                <flux:callout variant="subtle" icon="information-circle" class="text-slate-200">
                    {{ __('No trusted devices yet. Add one above to fast-track your routine actions while keeping intrusion detection intact.') }}
                </flux:callout>
            @else
                <div class="overflow-hidden rounded-2xl border border-white/10">
                    <table class="min-w-full divide-y divide-white/10 text-left text-sm text-slate-200">
                        <thead class="bg-white/5 text-xs uppercase tracking-wide text-slate-400">
                            <tr>
                                <th scope="col" class="px-4 py-3 font-semibold">{{ __('Label') }}</th>
                                <th scope="col" class="px-4 py-3 font-semibold">{{ __('Last seen') }}</th>
                                <th scope="col" class="px-4 py-3 font-semibold">{{ __('Status') }}</th>
                                <th scope="col" class="px-4 py-3 font-semibold">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @foreach ($devices as $device)
                                <tr
                                    wire:key="trusted-device-{{ $device['public_id'] }}"
                                    class="bg-slate-950/40 transition-colors hover:bg-slate-900/60 {{ $device['highlight'] ? 'ring-2 ring-emerald-400/60' : '' }}"
                                >
                                    <td class="px-4 py-4 align-top">
                                        <div class="space-y-2">
                                            <div class="flex items-center gap-3">
                                                @if ($device['is_current'])
                                                    <flux:badge variant="solid" color="emerald">{{ __('This device') }}</flux:badge>
                                                @endif

                                                @if ($device['is_active'])
                                                    <flux:badge variant="outline" color="sky">{{ __('Active') }}</flux:badge>
                                                @else
                                                    <flux:badge variant="outline" color="red">{{ __('Revoked') }}</flux:badge>
                                                @endif
                                            </div>
                                            <flux:input
                                                wire:model.defer="renameLabels.{{ $device['public_id'] }}"
                                                maxlength="120"
                                                class="w-full"
                                            />
                                            <p class="text-xs text-slate-400">
                                                {{ $device['ip_address'] ?: __('IP unknown') }} &bull;
                                                {{ $device['user_agent'] ? \Illuminate\Support\Str::limit($device['user_agent'], 60) : __('User agent unavailable') }}
                                            </p>
                                            @if (! empty($device['metadata']))
                                                <p class="text-xs text-slate-500">
                                                    {{ collect($device['metadata'])->filter()->implode(', ') }}
                                                </p>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 align-top">
                                        <div class="flex flex-col gap-1 text-xs text-slate-400">
                                            <span>{{ __('Last used :time', ['time' => $device['last_used_at'] ?? __('never')]) }}</span>
                                            <span>{{ __('Trusted :time', ['time' => $device['first_trusted_at'] ?? __('unknown')]) }}</span>
                                            @if ($device['expires_at'])
                                                <span>{{ __('Expires :time', ['time' => $device['expires_at']]) }}</span>
                                            @endif
                                            @if ($device['revoked_at'])
                                                <span class="text-red-300">{{ __('Revoked :time', ['time' => $device['revoked_at']]) }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 align-top">
                                        <div class="flex flex-col gap-2 text-xs text-slate-300">
                                            @if ($device['is_active'])
                                                <span>{{ __('Full access') }}</span>
                                            @else
                                                <span class="text-red-300">{{ __('Trust removed') }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 align-top">
                                        <div class="flex flex-col gap-2">
                                            <flux:button
                                                size="sm"
                                                variant="ghost"
                                                icon="pencil-square"
                                                wire:click="renameDevice('{{ $device['public_id'] }}')"
                                                class="justify-start text-slate-300 hover:text-white"
                                            >
                                                {{ __('Save label') }}
                                            </flux:button>
                                            <flux:button
                                                size="sm"
                                                variant="ghost"
                                                icon="trash"
                                                wire:click="revokeDevice('{{ $device['public_id'] }}')"
                                                class="justify-start text-red-300 hover:text-red-100"
                                            >
                                                {{ __('Revoke trust') }}
                                            </flux:button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
