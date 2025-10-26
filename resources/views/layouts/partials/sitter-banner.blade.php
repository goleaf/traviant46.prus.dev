@php
    $delegation = $context['delegation'] ?? [];
    $permissions = $delegation['permissions'] ?? [];
    $sitter = $context['sitter'] ?? [];
    $account = $context['account'] ?? [];

    $ownerHandle = $account['username'] ?? null;
    $sitterHandle = $sitter['username'] ?? null;
    $ownerLabel = $ownerHandle !== null ? '@' . $ownerHandle : __('unknown account');
    $sitterLabel = $sitterHandle !== null ? '@' . $sitterHandle : null;
@endphp

<div class="border-b border-amber-400/30 bg-amber-500/15 text-amber-100 shadow-[0_35px_120px_-60px_rgba(251,191,36,0.65)]">
    <flux:container class="py-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-col gap-2">
                <div class="flex items-center gap-3">
                    <div class="flex size-10 items-center justify-center rounded-full bg-amber-500/25 text-amber-200">
                        <flux:icon name="arrows-right-left" class="size-5" />
                    </div>

                    <div class="space-y-1">
                        <p class="text-sm font-semibold">
                            {{ __('Acting as: :owner', ['owner' => $ownerLabel]) }}
                        </p>

                        @if ($sitterLabel !== null)
                            <p class="text-xs text-amber-200/80">
                                {{ __('Signed in via sitter :sitter', ['sitter' => $sitterLabel]) }}
                            </p>
                        @endif

                        @if (! ($delegation['present'] ?? false))
                            <p class="text-xs text-amber-200/80">
                                {{ __('This delegation has been revoked. Please sign out to avoid losing progress.') }}
                            </p>
                        @elseif (! empty($delegation['expires_human']))
                            <p class="text-xs text-amber-200/80">
                                {{ __('Access expires :time', ['time' => $delegation['expires_human']]) }}
                            </p>
                        @endif
                    </div>
                </div>

                @if (! empty($permissions))
                    <div class="pl-12 flex flex-wrap gap-2">
                        @foreach ($permissions as $permission)
                            <flux:badge variant="outline" color="amber" class="border-amber-400/50 bg-amber-500/10 text-amber-100">
                                {{ $permission['label'] }}
                            </flux:badge>
                        @endforeach
                    </div>
                @endif
            </div>

            <form method="POST" action="{{ route('logout') }}" class="shrink-0">
                @csrf

                <flux:button type="submit" variant="solid" color="amber" size="sm">
                    {{ __('Leave') }}
                </flux:button>
            </form>
        </div>
    </flux:container>
</div>
