<div class="flex min-h-screen flex-col items-center justify-center bg-slate-950/95 px-6 py-16 text-center text-white">
    <div class="w-full max-w-2xl space-y-6 rounded-3xl border border-white/10 bg-slate-900/70 p-12 shadow-[0_40px_120px_-30px_rgba(56,189,248,0.35)] backdrop-blur">
        <div class="flex justify-center">
            <flux:icon name="wrench-screwdriver" class="size-16 text-sky-300" />
        </div>

        <flux:heading level="1" size="lg" class="text-white">
            {{ __('World undergoing maintenance') }}
        </flux:heading>

        <flux:description class="text-slate-300">
            {{ __('We are applying balance changes and infrastructure upgrades. Your villages are safe and timers are paused until we reopen the gates.') }}
        </flux:description>

        <div class="rounded-2xl border border-white/10 bg-slate-950/50 p-6 text-left text-sm text-slate-200">
            <p class="text-xs uppercase tracking-wide text-slate-400">{{ __('Scheduled start') }}</p>
            <p class="mt-2 font-semibold">
                {{ $startTime ? \Illuminate\Support\Carbon::parse($startTime)->timezone(config('app.timezone'))->isoFormat('MMM D, YYYY • HH:mm') : __('Announced soon') }}
            </p>
        </div>

        <div class="space-y-3 text-sm text-slate-400">
            <p>{{ __('You will be automatically redirected once maintenance concludes. Check Discord or the Travian status page for live updates.') }}</p>
            <p>
                <a href="https://status.travian.example" target="_blank" rel="noreferrer" class="font-semibold text-sky-300 hover:text-sky-200">
                    {{ __('Visit status portal') }}
                </a>
            </p>
        </div>
    </div>
</div>
