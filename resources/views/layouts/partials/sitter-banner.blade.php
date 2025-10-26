@php
    $delegation = $context['delegation'] ?? [];
    $permissions = $delegation['permissions'] ?? [];
    $sitter = $context['sitter'] ?? [];
    $account = $context['account'] ?? [];
@endphp

<div class="bg-amber-500/10 border-b border-amber-400/30 backdrop-blur-sm shadow-[0_35px_120px_-60px_rgba(251,191,36,0.65)]">
    <flux:container class="py-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex items-start gap-3 text-amber-100">
                <div class="flex size-10 items-center justify-center rounded-full bg-amber-500/20">
                    <flux:icon name="arrows-right-left" class="size-5" />
                </div>

                <div class="space-y-1">
                    <p class="text-xs font-semibold uppercase tracking-wide text-amber-300">
                        {{ __('Delegation context') }}
                    </p>

                    <p class="text-sm leading-relaxed">
                        {{ __('You are acting as :sitter on behalf of :owner.', [
                            'sitter' => $sitter['username'] ?? __('unknown sitter'),
                            'owner' => $account['username'] ?? __('unknown account'),
                        ]) }}
                    </p>

                    @if (! ($delegation['present'] ?? false))
                        <p class="text-xs text-amber-200/80">
                            {{ __('This delegation has been revoked. Please sign out to avoid losing progress.') }}
                        </p>
                    @endif
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @if ($delegation['present'] ?? false)
                    @if (! empty($delegation['preset_label']))
                        <flux:badge variant="solid" color="amber" class="text-xs uppercase tracking-wide">
                            {{ $delegation['preset_label'] }}
                        </flux:badge>
                    @endif

                    @if (! empty($delegation['expires_human']))
                        <span class="text-xs text-amber-200/90">
                            {{ __('Expires :time', ['time' => $delegation['expires_human']]) }}
                        </span>
                    @endif
                @else
                    <flux:badge variant="solid" color="red" class="text-xs uppercase tracking-wide">
                        {{ __('Revoked') }}
                    </flux:badge>
                @endif
            </div>
        </div>

        @if (! empty($permissions))
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach ($permissions as $permission)
                    <flux:badge variant="outline" color="amber" class="border-amber-400/50 bg-amber-500/10 text-amber-100">
                        {{ $permission['label'] }}
                    </flux:badge>
                @endforeach
            </div>
        @endif
    </flux:container>
</div>
