<div class="village-overview">
    <header class="village-overview__header">
        <h1>{{ $overview['villageName'] }}</h1>
        <p class="village-overview__coordinates">
            ({{ data_get($overview, 'coordinates.x') }}, {{ data_get($overview, 'coordinates.y') }})
        </p>
        <p class="village-overview__population">Population: {{ $overview['population'] }}</p>
    </header>

    <section class="village-overview__production">
        <h2>Production</h2>
        <ul>
            <li>Wood: {{ data_get($overview, 'production.wood') }}</li>
            <li>Clay: {{ data_get($overview, 'production.clay') }}</li>
            <li>Iron: {{ data_get($overview, 'production.iron') }}</li>
            <li>Crop: {{ data_get($overview, 'production.crop') }}</li>
        </ul>
    </section>
</div>
