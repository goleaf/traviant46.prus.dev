<div class="flex min-h-screen flex-col items-center justify-center bg-slate-950/90 px-6 py-16 text-center text-white">
    <div class="w-full max-w-xl space-y-6 rounded-3xl border border-white/10 bg-slate-900/60 p-10 shadow-2xl shadow-sky-900/20 backdrop-blur">
        <div class="flex justify-center">
            <flux:icon name="no-symbol" class="size-16 text-rose-400" />
        </div>

        <flux:heading level="1" size="lg" class="text-white">
            {{ __('Access temporarily suspended') }}
        </flux:heading>

        <flux:description class="text-slate-300">
            {{ __('Your account has been banned from the world. You can still review ban details and appeal the decision from here.') }}
        </flux:description>

        @if($user?->ban_reason)
            <div class="rounded-2xl bg-rose-500/10 p-5 text-left text-sm text-rose-200">
                <p class="font-semibold uppercase tracking-wide text-rose-300">
                    {{ __('Ban reason') }}
                </p>
                <p class="mt-2 whitespace-pre-line">{{ $user->ban_reason }}</p>
            </div>
        @endif

        <div class="grid gap-4 text-left text-sm text-slate-200 sm:grid-cols-2">
            <div class="rounded-2xl border border-white/10 bg-slate-950/50 p-4">
                <p class="text-xs uppercase tracking-wide text-slate-400">{{ __('Issued at') }}</p>
                <p class="mt-2 font-semibold">
                    {{ optional($user?->ban_issued_at)->timezone($user?->timezone ?? config('app.timezone'))?->isoFormat('MMM D, YYYY • HH:mm') ?? __('Unknown') }}
                </p>
            </div>

            <div class="rounded-2xl border border-white/10 bg-slate-950/50 p-4">
                <p class="text-xs uppercase tracking-wide text-slate-400">{{ __('Expires at') }}</p>
                <p class="mt-2 font-semibold">
                    {{ optional($user?->ban_expires_at)->timezone($user?->timezone ?? config('app.timezone'))?->isoFormat('MMM D, YYYY • HH:mm') ?? __('Indefinite') }}
                </p>
            </div>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:justify-center">
            <flux:button as="a" href="mailto:support@travian.example" icon="envelope" variant="primary" color="sky">
                {{ __('Contact support') }}
            </flux:button>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <flux:button type="submit" icon="arrow-right-start-on-rectangle" variant="ghost" color="slate">
                    {{ __('Sign out') }}
                </flux:button>
            </form>
        </div>
    </div>
</div>
