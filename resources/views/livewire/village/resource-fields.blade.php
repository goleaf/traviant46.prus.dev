<div class="village-resource-fields">
    <h1>Resource Fields</h1>

    <section class="village-resource-fields__production">
        <h2>Production per hour</h2>
        <ul>
            <li>Wood: {{ $production['wood'] }}</li>
            <li>Clay: {{ $production['clay'] }}</li>
            <li>Iron: {{ $production['iron'] }}</li>
            <li>Crop: {{ $production['crop'] }}</li>
        </ul>
    </section>

    <section class="village-resource-fields__grid">
        <h2>Fields</h2>
        @if (empty($fields))
            <p>No fields configured.</p>
        @else
            <div class="village-resource-fields__grid-layout">
                @foreach ($fields as $field)
                    <article class="village-resource-fields__field">
                        <header>
                            <h3>{{ data_get($field, 'name', 'Field') }}</h3>
                            <span>Level {{ data_get($field, 'level', 0) }}</span>
                        </header>
                        <p>Type: {{ data_get($field, 'type', 'unknown') }}</p>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</div>
