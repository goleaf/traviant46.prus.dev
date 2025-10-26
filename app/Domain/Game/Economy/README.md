# Economy Domain

This namespace centralises services that balance resource production, upkeep, and other economic levers that impact gameplay.

## Responsibilities
- Compute hourly production, storage caps, and consumption modifiers across all resources.
- Evaluate upkeep and starvation rules as troops and buildings change state.
- Surface helper methods for marketplace valuations, taxes, and economic buffs or penalties.

## Service Guidelines
- Make services deterministic so the same input yields the same resource deltas.
- Coordinate persistence through repositories while keeping domain calculations encapsulated here.
