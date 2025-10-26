# Troop Domain

This namespace centralises troop lifecycle services covering recruitment, statistics, and movement readiness.

## Responsibilities
- Calculate training times, costs, and queue behaviour across buildings and tribes.
- Provide stat lookups and modifiers for units, heroes, and special troops.
- Coordinate upkeep adjustments and availability checks for marches or reinforcements.

## Service Guidelines
- Keep services pure and deterministic; defer persistence to repositories or dedicated actions.
- Inject collaborating domains (economy, building, combat) instead of accessing them statically.
