<div class="tile-details">
    <h2 class="tile-details__title">Tile ({{ $coordinates['x'] }}, {{ $coordinates['y'] }})</h2>

    <dl class="tile-details__list">
        <div>
            <dt>Terrain</dt>
            <dd>{{ ucfirst($details['terrain']) }}</dd>
        </div>
        <div>
            <dt>Description</dt>
            <dd>{{ $details['description'] }}</dd>
        </div>
    </dl>

    <h3 class="tile-details__subtitle">Resource abundance</h3>
    <ul class="tile-details__resources">
        @foreach ($details['abundance'] as $resource => $amount)
            <li>{{ ucfirst($resource) }}: {{ $amount }}%</li>
        @endforeach
    </ul>
</div>
