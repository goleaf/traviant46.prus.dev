# Building Domain

Use this namespace for services that orchestrate building construction, upgrades, and demolition rules across villages.

## Responsibilities
- Validate prerequisites, resource costs, and builder availability before scheduling work.
- Track upgrade timers, queues, and master builder behaviour for each slot.
- Apply building effects that influence other systems such as economy boosts or unit unlocks.

## Service Guidelines
- Keep calculations deterministic so they can run in queues, Livewire components, or console commands.
- Prefer collaborating with repositories or actions for persistence to maintain testable service classes.
