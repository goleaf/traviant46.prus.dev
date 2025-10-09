<div>
    <section class="card">
        <h1>{{ __('Alliance Tools') }}</h1>
        <p style="color:#94a3b8;">{{ __('Coordinate members, review diplomacy, and manage roles from a single control surface.') }}</p>
        @isset($data['empty_state'])
            <p style="color:#38bdf8; background:rgba(56,189,248,0.12); padding:0.75rem 1rem; border-radius:0.75rem;">{{ $data['empty_state'] }}</p>
        @endisset
        <div style="margin-top:1.5rem; display:grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap:1.5rem;">
            <div>
                <h3>{{ $data['alliance']['name'] }} [{{ $data['alliance']['tag'] }}]</h3>
                <p style="margin:0;">{{ $data['alliance']['description'] }}</p>
                @if($data['alliance']['motd'])
                    <p style="margin:0.75rem 0 0; padding:1rem; border-radius:0.75rem; background:rgba(59,130,246,0.15);">{{ $data['alliance']['motd'] }}</p>
                @endif
            </div>
            <div>
                <h3>{{ __('Diplomacy') }}</h3>
                <p style="margin:0; font-size:0.9rem; color:#cbd5f5;">{{ __('Confederacies: :count', ['count' => count($data['diplomacy']['confederacies'] ?? [])]) }}</p>
                <p style="margin:0.35rem 0 0; font-size:0.9rem; color:#cbd5f5;">{{ __('NAPs: :count', ['count' => count($data['diplomacy']['non_aggression_pacts'] ?? [])]) }}</p>
                <p style="margin:0.35rem 0 0; font-size:0.9rem; color:#cbd5f5;">{{ __('Wars: :count', ['count' => count($data['diplomacy']['wars'] ?? [])]) }}</p>
            </div>
        </div>
    </section>

    <section class="card">
        <h2>{{ __('Members') }}</h2>
        <table>
            <thead>
            <tr>
                <th>{{ __('Player') }}</th>
                <th>{{ __('Role') }}</th>
                <th>{{ __('Permissions') }}</th>
            </tr>
            </thead>
            <tbody>
            @forelse($data['members'] as $member)
                <tr>
                    <td>{{ $member['username'] }} <span style="color:#94a3b8;">({{ $member['name'] }})</span></td>
                    <td style="text-transform:capitalize;">{{ __($member['role']) }}</td>
                    <td>
                        @if(empty($member['permissions']))
                            <span>{{ __('Default access') }}</span>
                        @else
                            @foreach($member['permissions'] as $permission)
                                <span style="background:rgba(148,163,184,0.15); border-radius:999px; padding:0.25rem 0.6rem; margin-right:0.35rem; font-size:0.8rem; text-transform:uppercase; letter-spacing:0.05em;">{{ __($permission) }}</span>
                            @endforeach
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" style="text-align:center; color:#94a3b8;">{{ __('No members found.') }}</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </section>
</div>
