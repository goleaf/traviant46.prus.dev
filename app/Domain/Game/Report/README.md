# Report Domain

Services here assemble the structured summaries and notifications generated after combats, scouting, trades, and other events.

## Responsibilities
- Compile combat, economy, and quest data into reusable report payloads.
- Format report metadata for inbox lists, sharing links, and archival storage.
- Provide helpers that mark reports as read, archived, or actionable for downstream systems.

## Service Guidelines
- Keep report composition deterministic so identical inputs produce identical payloads.
- Avoid coupling services to rendering so Livewire components, APIs, and jobs can reuse the same builders.
