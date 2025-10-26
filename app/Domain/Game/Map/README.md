# Map Domain

This namespace owns the services that understand the world map, tile metadata, and navigation helpers needed by the game loop.

## Responsibilities
- Translate between coordinates, map tiles, and village identifiers.
- Provide helpers for neighbourhood lookups, oasis metadata, and region summaries.
- Surface reusable calculations for distance, travel costs, and map-based filters for other domains.

## Service Guidelines
- Keep service classes framework-agnostic so they can be reused by jobs, Livewire components, or actions.
- Delegate persistence to repositories or models—map services should focus on orchestration and calculations.
