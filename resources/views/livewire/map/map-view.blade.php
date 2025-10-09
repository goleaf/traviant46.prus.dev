<div class="map-view">
    <div class="map-controls">
        <div class="zoom-controls">
            <button type="button" wire:click="zoomOut" aria-label="Zoom out">−</button>
            <span class="zoom-level">Zoom: {{ $zoomLevel }}</span>
            <button type="button" wire:click="zoomIn" aria-label="Zoom in">+</button>
        </div>
        <div class="pan-controls">
            <button type="button" wire:click="pan('north')" aria-label="Pan north">↑</button>
            <div class="pan-controls__middle">
                <button type="button" wire:click="pan('west')" aria-label="Pan west">←</button>
                <button type="button" wire:click="pan('east')" aria-label="Pan east">→</button>
            </div>
            <button type="button" wire:click="pan('south')" aria-label="Pan south">↓</button>
        </div>
    </div>

    <div class="map-grid" role="grid" aria-label="World map grid">
        @foreach ($tiles as $row)
            <div class="map-grid__row" role="row">
                @foreach ($row as $tile)
                    <button
                        type="button"
                        class="map-grid__tile {{ $tile['isCenter'] ? 'map-grid__tile--center' : '' }}"
                        wire:click="select({{ $tile['x'] }}, {{ $tile['y'] }})"
                        role="gridcell"
                        aria-label="Tile {{ $tile['x'] }}, {{ $tile['y'] }}"
                    >
                        <span class="map-grid__coords">{{ $tile['x'] }}, {{ $tile['y'] }}</span>
                    </button>
                @endforeach
            </div>
        @endforeach
    </div>

    <div class="map-view__footer">
        <span>Center: ({{ $center['x'] }}, {{ $center['y'] }})</span>
    </div>
</div>
