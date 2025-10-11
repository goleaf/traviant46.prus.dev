<div class="village-list">
    <h1>Villages</h1>
    @if (empty($villages))
        <p>You do not control any villages yet.</p>
    @else
        <ul>
            @foreach ($villages as $village)
                <li>
                    <strong>{{ data_get($village, 'name', 'Unnamed Village') }}</strong>
                    <span>Population: {{ data_get($village, 'population', 0) }}</span>
                    <span>({{ data_get($village, 'coordinates.x', 0) }}, {{ data_get($village, 'coordinates.y', 0) }})</span>
                </li>
            @endforeach
        </ul>
    @endif
</div>
