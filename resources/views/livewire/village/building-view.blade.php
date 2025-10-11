<div class="village-building-view">
    <header>
        <h1>Building Slot {{ $building['slot'] ?? '—' }}</h1>
        <p>{{ $building['name'] ?? 'No building selected' }}</p>
        <p>Level {{ $building['level'] ?? 0 }}</p>
    </header>

    <section class="village-building-view__queue">
        <h2>Construction Queue</h2>
        @if (empty($building['queue']))
            <p>No construction queued.</p>
        @else
            <ol>
                @foreach ($building['queue'] as $item)
                    <li>
                        <strong>{{ data_get($item, 'name') }}</strong>
                        <span>Level {{ data_get($item, 'level') }}</span>
                        <span>Finishes at {{ data_get($item, 'finishes_at') }}</span>
                    </li>
                @endforeach
            </ol>
        @endif
    </section>
</div>
