# Artifact Tables Migration Blueprint

## Legacy Schema Snapshot

### `artefacts`
The legacy world keeps one row per spawned artefact, tracking ownership (`uid`), the village holding it (`kid`), its spawn location (`release_kid`), rarity (`size`), conquest timestamps, and the applied effect metadata (`type`, `effecttype`, `effect`, `aoe`, `status`, `active`).【F:main_script/include/schema/T4.4.sql†L278-L302】  `lastupdate` is repurposed by background jobs when recalculating "fool" artefact effects, while `conquered` stores the UNIX time of the last capture event.【F:main_script/include/App/Services/ArtifactService.php†L317-L350】【F:main_script_dev/include/Core/Automation.php†L213-L228】

### `artlog`
`artlog` keeps an ordered capture history for each artefact, capturing the artefact id, conquering player (`uid`/`name`), target village (`kid`), and a UNIX timestamp for display in the treasury detail view.【F:main_script/include/schema/T4.4.sql†L305-L314】【F:main_script_dev/include/Controller/Build/TreasuryCtrl.php†L63-L98】

## How the Legacy Code Uses Them
- `ArtifactService::captureArtefact` updates the owning user and village, inserts a `artlog` row, and enforces activation ordering by toggling the `status` and `active` flags.【F:main_script_dev/include/App/Services/ArtifactService.php†L317-L376】
- `ArtifactService::Artifact` respawns artefacts at their original `release_kid` village (or finds a new neutral spawn), seeds them with Natar ownership (`uid = 1`), and stores per-instance metadata like `num`, `effecttype`, and `aoe` in `artefacts`.【F:main_script_dev/include/App/Services/ArtifactService.php†L194-L272】
- `AccountDeleter` blocks village deletions when the player still holds an artefact in that settlement, relying on the `uid` + `kid` combination in `artefacts`.【F:main_script_dev/include/Model/AccountDeleter.php†L53-L104】
- Automation jobs poll `artefacts` to activate captures after the 12h/24h delay (`active` flag) and to cycle fool artefact effects, depending on `lastupdate` and `status` fields.【F:main_script_dev/include/Core/Automation.php†L213-L228】【F:main_script_dev/include/App/Services/ArtifactService.php†L567-L650】
- Treasury UI queries `artefacts` for ownership lists and `artlog` for capture history when rendering management screens.【F:main_script_dev/include/Controller/Build/TreasuryCtrl.php†L63-L98】

## Pain Points in the Legacy Design
- No foreign keys tie `uid` and `kid` to the `users`/`vdata` tables, so dangling artefacts can occur when accounts or villages are deleted.
- Gameplay states are encoded as integers (`status`, `active`, `aoe`) without documentation, making it hard to map to enums or booleans during the migration.
- UNIX timestamps in `conquered`, `lastupdate`, and `artlog.time` need conversion to timezone-aware `timestamp` columns.
- `effecttype` overloads multiple behaviours (especially for architect artefacts) without referencing a dictionary table, which makes seeding deterministic effect values difficult.
- Respawn logic duplicates spawn coordinates inside each row (`release_kid`) instead of referencing the neutral village entity created for the artefact.

## Proposed Laravel Schema

### `artifact_templates`
Dictionary that defines immutable metadata per artefact template (e.g., type, size, effect variant, default bonuses, spawn troop composition). This replaces hard-coded branching on `type`, `size`, `num`, and `effecttype` during respawn and activation.

| Column | Type | Notes |
| --- | --- | --- |
| `id` | big increments | Primary key. |
| `game_code` | string | Maps to Travian artefact identifiers (e.g., `architect`, `fool`, `plan`). |
| `size` | enum(`village`,`account`,`unique`) | Replaces numeric `size`/`aoe`. |
| `effect_variant` | string | Distinguishes architect effect tiers, victory-point plan vs. regular artefact, etc. |
| `base_effect` | decimal(8,3) | Raw multiplier before speed modifiers. |
| `base_troop_preset` | json | Serialises garrison counts currently derived in `getArtifactUnits()`. |
| `activation_delay_hours` | unsigned smallint | Usually 12 or 24 depending on world speed. |
| `created_at`/`updated_at` | timestamps | Audit trail. |

### `artifacts`
Represents each spawned artefact instance.

| Column | Type | Notes |
| --- | --- | --- |
| `id` | big increments | Primary key. |
| `template_id` | foreignId → `artifact_templates` | Replaces `type`/`effecttype`/`aoe`/`num`. |
| `spawn_village_id` | foreignId → `villages` | Replaces `release_kid`. Nullable until templates are seeded. |
| `holder_village_id` | foreignId → `villages` nullable | Replaces `kid` (`NULL` when unassigned). |
| `holder_user_id` | foreignId → `users` nullable | Replaces `uid` (`NULL` when Natar-owned). |
| `captured_at` | timestamp nullable | Converts `conquered`. |
| `activated_at` | timestamp nullable | Derived from `captured_at` + delay, replaces `active` toggle. |
| `status` | enum(`neutral`,`captured`,`disabled`,`queued_respawn`) | Encodes existing `status` meanings. |
| `is_active` | boolean | Mirrors activation flag but uses true/false. |
| `effect_override` | decimal(8,3) nullable | Allows storing per-instance adjustments from `effect`. |
| `effect_last_refreshed_at` | timestamp nullable | Replaces `lastupdate` for fool artefacts. |
| `respawn_requested_at` | timestamp nullable | Set when `clearArtifactFromVillage` runs. |
| `created_at`/`updated_at` | timestamps | Audit columns. |

### `artifact_logs`
Historical capture log replacing `artlog`.

| Column | Type | Notes |
| --- | --- | --- |
| `id` | big increments | Primary key. |
| `artifact_id` | foreignId → `artifacts` | Replaces `artId`. |
| `captor_user_id` | foreignId → `users` | Replaces `uid`. |
| `captor_village_id` | foreignId → `villages` | Replaces `kid`. |
| `captor_name_snapshot` | string(40) | Preserves legacy display name without relying on joins. |
| `captured_at` | timestamp | Converts `time`. |
| `created_at` | timestamp | For auditing; defaults to `captured_at`. |

## Migration Workflow
1. Seed `artifact_templates` from configuration, mapping each `type`/`size`/`effecttype`/`num` combination encountered in `artefacts` to a deterministic template row with the appropriate `base_effect`.
2. Create `artifacts` and `artifact_logs` tables with foreign keys to `users` and `villages` so cascades remove dangling captures automatically.
3. Backfill `artifacts` by joining legacy `artefacts` with `users` (`uid`) and `vdata` (`kid`, `release_kid`), resolving each template id based on the legacy columns. Convert UNIX epoch columns to UTC timestamps during the insert.
4. Populate `artifact_logs` from `artlog`, mapping `artId` to the newly inserted artefact id, capturing the player's historical name, and converting the capture UNIX epoch to timestamps.
5. Update dependent migrations to drop the legacy tables only after the data is copied and validated.
6. Introduce foreign key constraints and indexes covering activation checks (e.g., `(status, activated_at)`), holder lookups (`holder_user_id`, `holder_village_id`), and respawn queries (`status`, `spawn_village_id`).

## Application Refactor Notes
- Replace raw SQL in `ArtifactService` with Eloquent models that reference `Artifact`, `ArtifactTemplate`, and `ArtifactLog`. Activation and respawn routines should use relationships instead of manual `uid`/`kid` assignments.【F:main_script_dev/include/App/Services/ArtifactService.php†L194-L376】
- Treasury Livewire components should eager-load `artifactLogs` relations to replace manual queries and leverage accessor methods for localized names and effects.【F:main_script_dev/include/Controller/Build/TreasuryCtrl.php†L63-L98】
- Account deletion logic can now rely on `Artifact::whereHolderVillageId($kid)` instead of counting raw rows, simplifying invariants and ensuring cascades clean up artefacts when a village is destroyed.【F:main_script_dev/include/Model/AccountDeleter.php†L53-L104】
- Scheduler jobs should query by `status`/`activated_at` timestamps to determine when to toggle artefact effects, rather than comparing UNIX integers and manual `active` flags.【F:main_script_dev/include/Core/Automation.php†L213-L228】
