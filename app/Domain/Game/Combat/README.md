# Combat Domain

Services under this namespace are responsible for simulating battles and resolving their outcomes across the game world.

## Responsibilities
- Calculate damage, morale, and casualty results for every engagement type.
- Apply scouting, trapping, and hero modifiers during combat resolution.
- Generate structured payloads consumed by reports, notifications, and movement processors.

## Service Guidelines
- Keep formulas pure and stateless to simplify automated testing.
- Use collaborator services (troop, report, map) through dependency injection to avoid circular dependencies.
