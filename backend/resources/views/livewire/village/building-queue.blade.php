<div>
    <table>
        <thead>
        <tr>
            <th>{{ __('Slot') }}</th>
            <th>{{ __('Building') }}</th>
            <th>{{ __('Target Level') }}</th>
            <th>{{ __('Finishes At') }}</th>
            <th>{{ __('Cost') }}</th>
        </tr>
        </thead>
        <tbody>
        @foreach($queue as $entry)
            <tr>
                <td>#{{ $entry['slot'] }}</td>
                <td>{{ config('game.building_types.' . ($entry['building'] ?? '') . '.name', ucfirst(str_replace('_', ' ', $entry['building'] ?? ''))) }}</td>
                <td>{{ $entry['target_level'] }}</td>
                <td>{{ $entry['finishes_at'] ? \Illuminate\Support\Carbon::parse($entry['finishes_at'])->toDayDateTimeString() : __('Pending') }}</td>
                <td>
                    @if(!empty($entry['cost']))
                        @foreach($entry['cost'] as $resource => $value)
                            <span style="margin-right:0.5rem; text-transform:capitalize;">{{ __($resource) }}: {{ $value }}</span>
                        @endforeach
                    @else
                        <span>{{ __('Unknown') }}</span>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
