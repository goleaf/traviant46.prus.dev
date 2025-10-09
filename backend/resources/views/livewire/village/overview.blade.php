<div>
    <section class="card">
        <h1>{{ __('Village Overview') }}</h1>
        <p style="color:#94a3b8; margin-top:0.25rem;">{{ __('A consolidated view of your settlement status, production, and construction queues.') }}</p>
        @isset($overview['empty_state'])
            <p style="color:#f87171; background:rgba(248,113,113,0.1); padding:0.75rem 1rem; border-radius:0.75rem;">{{ $overview['empty_state'] }}</p>
        @endisset
        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:1.5rem; margin-top:1.5rem;">
            <div>
                <h3>{{ $overview['village']['name'] }}</h3>
                <p style="margin:0; color:#cbd5f5;">{{ __('Population: :population', ['population' => $overview['village']['population']]) }}</p>
                <p style="margin:0.25rem 0 0; color:#cbd5f5;">{{ __('Coordinates: (:x|:y)', ['x' => $overview['village']['coordinates']['x'], 'y' => $overview['village']['coordinates']['y']]) }}</p>
            </div>
            <div>
                <h3>{{ __('Storage') }}</h3>
                <p style="margin:0;">{{ __('Warehouse: :capacity', ['capacity' => $overview['storage']['warehouse']]) }}</p>
                <p style="margin:0.25rem 0 0;">{{ __('Granary: :capacity', ['capacity' => $overview['storage']['granary']]) }}</p>
            </div>
            <div>
                <h3>{{ __('Production per hour') }}</h3>
                @foreach($overview['production'] as $resource => $value)
                    <p style="margin:0; text-transform:capitalize;">{{ __($resource) }}: {{ $value }}</p>
                @endforeach
            </div>
        </div>
    </section>

    <section class="card">
        <h2>{{ __('Current Resources') }}</h2>
        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap:1rem;">
            @foreach($overview['storage']['resources'] as $resource => $value)
                <div style="background:rgba(148,163,184,0.1); padding:1rem; border-radius:0.75rem;">
                    <p style="margin:0; font-size:0.85rem; text-transform:uppercase; color:#94a3b8;">{{ __($resource) }}</p>
                    <p style="margin:0.4rem 0 0; font-size:1.25rem; font-weight:600;">{{ $value }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <section class="card">
        <h2>{{ __('Building Queue') }}</h2>
        @if(empty($overview['queues']))
            <p style="color:#94a3b8;">{{ __('No construction projects are scheduled.') }}</p>
        @else
            <livewire:village.building-queue :queue="$overview['queues']" />
        @endif
    </section>
</div>
