<div class="flex min-h-screen flex-col items-center justify-center bg-slate-950/90 px-6 py-16 text-center text-white">
    <div class="w-full max-w-xl space-y-6 rounded-3xl border border-white/10 bg-slate-900/60 p-10 shadow-2xl shadow-amber-900/15 backdrop-blur">
        <div class="flex justify-center">
            <flux:icon name="envelope-open" class="size-16 text-amber-300" />
        </div>

        <flux:heading level="1" size="lg" class="text-white">
            {{ __('Verify your email to continue') }}
        </flux:heading>

        <flux:description class="text-slate-300">
            {{ __('A verified email keeps your account secure and unlocks in-game messaging, auctions, and alliance forums.') }}
        </flux:description>

        <div class="rounded-2xl border border-white/10 bg-slate-950/50 p-5 text-left text-sm text-slate-200">
            <p class="text-xs uppercase tracking-wide text-slate-400">{{ __('Signed in as') }}</p>
            <p class="mt-2 font-semibold">{{ $user?->email }}</p>
        </div>

        <div class="space-y-3">
            <form method="POST" action="{{ route('verification.send') }}" class="flex flex-col gap-3 sm:flex-row sm:justify-center">
                @csrf
                <flux:button type="submit" variant="primary" color="amber" icon="paper-airplane">
                    {{ __('Resend verification email') }}
                </flux:button>
            </form>

            @if($resent)
                <p class="text-sm text-amber-200">
                    {{ __('A new verification link has been sent to your inbox.') }}
                </p>
            @endif
        </div>

        <div class="text-sm text-slate-400">
            {{ __('Need to update your contact address?') }}
            <a href="mailto:support@travian.example" class="font-semibold text-amber-200 hover:text-amber-100">
                {{ __('Contact support') }}
            </a>
        </div>
    </div>
</div>
