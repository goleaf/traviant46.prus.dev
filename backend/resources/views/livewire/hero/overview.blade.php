<div>
    <section class="card">
        <h1>{{ __('Hero Overview') }}</h1>
        <p style="color:#94a3b8;">{{ __('Track your hero status, gear, and attribute investment.') }}</p>
        @isset($hero['empty_state'])
            <p style="color:#fbbf24; background:rgba(251,191,36,0.12); padding:0.75rem 1rem; border-radius:0.75rem;">{{ $hero['empty_state'] }}</p>
        @endisset
        <div style="display:flex; flex-wrap:wrap; gap:2rem; margin-top:1.5rem; align-items:flex-start;">
            <div style="flex:1; min-width:220px;">
                <h3>{{ $hero['name'] }}</h3>
                <p style="margin:0;">{{ __('Level: :level', ['level' => $hero['level']]) }}</p>
                <p style="margin:0.2rem 0;">{{ __('Experience: :xp', ['xp' => $hero['experience']]) }}</p>
                <p style="margin:0;">{{ __('Health: :health%', ['health' => $hero['health']]) }}</p>
                <p style="margin:0.2rem 0 0;">{{ $hero['is_alive'] ? __('Status: Alive') : __('Status: Reviving') }}</p>
            </div>
            <div style="flex:1; min-width:260px;">
                <h3>{{ __('Attributes') }}</h3>
                <ul style="list-style:none; padding:0; margin:0;">
                    @foreach($hero['attributes'] as $attribute => $value)
                        <li style="margin:0.25rem 0; text-transform:capitalize;">{{ __($attribute) }}: {{ $value }}</li>
                    @endforeach
                </ul>
            </div>
            <div style="flex:1; min-width:260px;">
                <h3>{{ __('Equipment') }}</h3>
                @if(empty($hero['equipment']))
                    <p style="color:#94a3b8;">{{ __('No items equipped.') }}</p>
                @else
                    <ul style="list-style:none; padding:0; margin:0;">
                        @foreach($hero['equipment'] as $slot => $item)
                            <li style="margin:0.25rem 0; text-transform:capitalize;">{{ __($slot) }}: {{ $item['name'] ?? $item }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </section>
</div>
