@php
    $payload = session()->get('impersonation', []);
    $impersonatorName = $payload['impersonator_name'] ?? __('Administrator');
    $impersonatedName = $payload['impersonated_name'] ?? __('player');
@endphp

@if (session()->get('impersonation.active', false))
    <div class="border-b border-amber-400/30 bg-amber-500/15 text-amber-100">
        <flux:container class="flex flex-col gap-3 py-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1">
                <p class="text-sm font-semibold uppercase tracking-wide">
                    {{ __('Impersonation mode active') }}
                </p>
                <p class="text-sm text-amber-100/90">
                    {{ __(':admin is acting as :player. Activity is audited — resolve your investigation and exit as soon as possible.', [
                        'admin' => $impersonatorName,
                        'player' => $impersonatedName,
                    ]) }}
                </p>
            </div>

            <form method="POST" action="{{ route('impersonation.destroy') }}" class="shrink-0">
                @csrf
                @method('DELETE')

                <flux:button type="submit" variant="solid" color="amber" size="sm">
                    {{ __('Stop impersonating') }}
                </flux:button>
            </form>
        </flux:container>
    </div>
@endif
