# Market Domain

Use this namespace for services that power the in-game marketplace, traderoutes, and player-to-player commerce systems.

## Responsibilities
- Validate offers, trades, and auctions against player permissions and marketplace rules.
- Balance merchant capacity, travel time, and resource payloads for shipments.
- Provide matching and settlement helpers that integrate with economy and report domains.

## Service Guidelines
- Keep business rules decoupled from transport (HTTP, jobs) so they can run synchronously or in queues.
- Collaborate with repositories or aggregates to persist trades while keeping calculations and validation centralized here.
