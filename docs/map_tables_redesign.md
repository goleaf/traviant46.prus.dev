# Map Tables Redesign

## Overview
The legacy Travian map implementation stores rendered image caches and per-player overlays across five closely related tables: `map_block`, `map_mark`, `blocks`, `marks`, and `mapflag`. Cache invalidation relies on lightweight junction tables that record which tile IDs (`kid`) belong to each rasterized block, while `mapflag` tracks user/alliance highlights that are painted on top of rendered map segments.【F:main_script/include/schema/T4.4.sql†L382-L445】【F:main_script/include/Model/MapModel.php†L9-L125】【F:main_script/include/Controller/Map_markCtrl.php†L32-L245】

A sixth table, `surrounding`, logs time-bound neighborhood reports that are surfaced in the map/tile detail interface and pruned by automation routines.【F:main_script/include/schema/T4.4.sql†L1181-L1197】【F:main_script/include/Game/NoticeHelper.php†L78-L78】【F:main_script/include/Core/Automation.php†L412-L412】 Collectively, these structures power map exploration, alliance coordination, and event visibility.

### Current Schema Snapshot
- **`map_block`** – Defines cached background images for contiguous tile rectangles at varying zoom levels (`zoomLevel`) with a manual `version` counter for invalidation. Lacks `created_at`/`updated_at` timestamps and stores coordinate bounds directly.【F:main_script/include/schema/T4.4.sql†L409-L425】 The application creates rows on demand and increments versions when affected tiles change.【F:main_script/include/Model/MapModel.php†L46-L87】
- **`map_mark`** – Mirrors `map_block` but scoped to a specific user (`uid`) so personal/alliance overlays can be regenerated independently. Like `map_block`, it tracks bounding boxes and an integer version but omits ownership foreign keys and timestamps.【F:main_script/include/schema/T4.4.sql†L428-L445】【F:main_script/include/Model/MapModel.php†L27-L125】
- **`blocks` / `marks`** – Junction tables that associate tile IDs (`kid`) with the cached block (`map_id`) that includes them. They use `INT` identifiers without foreign keys or cascading deletes, making cache cleanup error-prone.【F:main_script/include/schema/T4.4.sql†L382-L425】【F:main_script/include/Model/MapModel.php†L9-L125】
- **`mapflag`** – Stores user and alliance “flags” or highlights for tiles/villages (`targetId`). The same table handles personal bookmarks (`uid`), alliance-wide markers (`aid`), and diplomacy highlights via a polymorphic `type` column, again without timestamps or relational integrity.【F:main_script/include/schema/T4.4.sql†L853-L867】【F:main_script/include/Controller/Map_markCtrl.php†L213-L235】【F:main_script/include/Controller/Ajax/mapFlagAdd.php†L47-L75】
- **`surrounding`** – Keeps the last few significant events near a tile (`kid`, `x`, `y`, `type`, `params`, `time`). Reports are trimmed periodically and queried when viewing tile details or surrounding reports.【F:main_script/include/schema/T4.4.sql†L1181-L1197】【F:main_script/include/Controller/Ajax/viewTileDetails.php†L514-L521】【F:main_script/include/Model/BerichteModel.php†L121-L131】

### Identified Pain Points
1. **No relational guarantees.** All six tables use loose integer references (`kid`, `uid`, `aid`, `targetId`) without foreign keys or cascading behavior, which invites orphaned rows and version drift when tiles/villages are deleted or alliances disband.【F:main_script/include/schema/T4.4.sql†L382-L445】【F:main_script/include/Model/MapModel.php†L75-L125】
2. **Manual cache versioning.** Incrementing `version` fields via custom loops over `blocks`/`marks` rows ties cache invalidation to bespoke SQL and offers no audit trail (`updated_at`).【F:main_script/include/Model/MapModel.php†L67-L125】
3. **Overloaded `mapflag` semantics.** A single table covers personal, alliance, and diplomatic highlights with ad-hoc `type` codes and duplicated entries (one for player, one for alliance) to represent shared marks, complicating synchronization and query filters.【F:main_script/include/Controller/Ajax/mapFlagAdd.php†L47-L75】【F:main_script/include/Controller/Ajax/mapMultiMarkAdd.php†L43-L75】
4. **Event history opacity.** `surrounding` stores serialized `params` text without structure, lacks references to villages/players, and is pruned by deleting rows older than three days, making analytics or long-term auditing impossible.【F:main_script/include/schema/T4.4.sql†L1181-L1197】【F:main_script/include/Core/Automation.php†L412-L412】

## Laravel-Oriented Target Design

### 1. Map Raster Cache
| Table | Purpose | Key Columns |
| --- | --- | --- |
| `map_tile_rasters` | Replaces `map_block`. Stores generated background images per zoom level with timestamps and storage metadata. | `id`, `zoom_level`, `top_left_x`, `top_left_y`, `bottom_right_x`, `bottom_right_y`, `version`, `rendered_at`, `invalidated_at`, `cache_path` |
| `map_tile_raster_tiles` | Replaces `blocks`. Junction linking rasters to individual tiles (`map_tiles.id`). Adds foreign keys and cascade delete. | `id`, `map_tile_raster_id` (FK), `map_tile_id` (FK) |

Enhancements:
- Enforce uniqueness on `(zoom_level, top_left_x, top_left_y, bottom_right_x, bottom_right_y)` to prevent duplicate rasters.
- Record `rendered_at`/`invalidated_at` timestamps instead of bare integer `version`, while keeping an auto-incremented `version` for client cache-busting.
- Use `map_tile_id` (FK into redesigned `map_tiles`/`wdata`) so cascades invalidate caches automatically when a tile is removed or reset.

### 2. Personal & Alliance Overlays
| Table | Purpose | Key Columns |
| --- | --- | --- |
| `map_overlay_rasters` | Replaces `map_mark`. Stores overlay images generated per user/alliance context. | `id`, `owner_type` (`user`/`alliance`), `owner_id`, `zoom_level`, bounding box columns, `version`, timestamps |
| `map_overlay_raster_tiles` | Replaces `marks`. Links overlays to tiles for targeted invalidation. | `id`, `map_overlay_raster_id` (FK), `map_tile_id` (FK) |

Changes:
- Collapse user-specific and alliance-specific caches into one table by adding `owner_type` enum; enforce foreign keys to `users` or `alliances` via polymorphic relationships.
- Store rendering metadata (`rendered_at`, `invalidated_at`, `rendered_by`) to aid debugging and queue-based regeneration.
- Replace manual `INSERT IGNORE` logic with queue jobs that populate `map_overlay_raster_tiles` based on tile membership, using Laravel events to invalidate related overlays when alliances change.

### 3. Map Flags & Highlights
| Table | Purpose | Key Columns |
| --- | --- | --- |
| `map_flags` | Normalizes `mapflag` data. | `id`, `owner_type` (`user`/`alliance`), `owner_id`, `target_type` (`tile`/`village`/`player`/`alliance`), `target_id`, `label`, `color_hex`, `visibility` (`private`/`shared`), `created_at`, `updated_at` |
| `map_flag_shares` | Optional table to model shared flags across alliances or sitter relationships. | `map_flag_id` (FK), `shared_with_type`, `shared_with_id`, permissions |

Benefits:
- Removes duplicate rows per flag by modeling sharing explicitly rather than inserting both player and alliance copies.【F:main_script/include/Controller/Ajax/mapFlagAdd.php†L47-L75】
- Enforces referential integrity to `users`, `alliances`, and `map_tiles` or `villages`, with cascading deletes or soft deletes.
- Supports auditing by capturing who created/updated flags and enabling soft deletes (`deleted_at`) for undo functionality.

### 4. Surrounding Reports
| Table | Purpose | Key Columns |
| --- | --- | --- |
| `surrounding_reports` | Replaces `surrounding`. Captures events near a tile with structured payloads. | `id`, `map_tile_id` (FK), `event_type`, `payload` (JSON), `occurred_at`, `expires_at`, `created_at` |
| `surrounding_report_audits` (optional) | Retains long-term history for analytics. | `id`, `surrounding_report_id`, `archived_at`, `archived_payload` |

Upgrades:
- Store event metadata in JSON (`payload`) with indexes on frequently filtered keys (e.g., `village_id`, `player_id`).
- Replace ad-hoc cron pruning with TTL-based `expires_at` and scheduled jobs; keep an archive table for reporting instead of hard deletes.【F:main_script/include/Core/Automation.php†L412-L412】

## Application Flow Adjustments
1. **Cache invalidation pipeline.** Wrap tile mutations (village creation, oasis capture, diplomacy changes) in Laravel domain events that dispatch jobs to invalidate affected raster/overlay rows via `map_tile_raster_tiles` and `map_overlay_raster_tiles` relationships, replacing manual SQL loops.【F:main_script/include/Model/MapModel.php†L67-L125】
2. **Rendering services.** Extract the logic from `Map_markCtrl` into queued jobs/services that read from `map_tile_rasters` / `map_overlay_rasters`, update `version`, store generated assets (e.g., S3/local disk), and emit cache busting events back to the client.【F:main_script/include/Controller/Map_markCtrl.php†L32-L245】
3. **Flag management APIs.** Introduce Laravel controllers that operate on `map_flags` with policy-based authorization (owner vs. alliance), emit events when flags are created or shared, and notify overlay caches to refresh relevant tiles.【F:main_script/include/Controller/Ajax/mapFlagAdd.php†L47-L75】
4. **Surrounding report consumers.** Update report queries to leverage eager-loaded relationships (`map_tile`, `village`, `player`) and render JSON payloads in Livewire components, enabling richer UI (e.g., filtering by event type, time range).【F:main_script/include/Controller/Ajax/viewTileDetails.php†L514-L521】

## Migration Strategy
1. **Shadow tables & dual writes.** Create the new Laravel tables alongside legacy ones and implement repository wrappers that dual-write to both schemas while read operations still rely on legacy data.
2. **Backfill data.** Generate raster-to-tile associations by iterating through existing `blocks`/`marks` rows and linking them to `map_tiles` (the redesigned `wdata`). Convert `mapflag` rows into `map_flags` with computed `owner_type`/`target_type` fields and import surrounding reports into JSON payloads.
3. **Toggle consumers.** Gradually update map rendering endpoints to read from the new tables, verifying correctness with side-by-side cache images and overlay counts.
4. **Cutover & cleanup.** Once Laravel services operate exclusively on the new schema, retire legacy tables (`map_block`, `map_mark`, `mapflag`, `marks`, `blocks`, `surrounding`) after validating cache warmers and report archives.

This redesign provides referential integrity, observability, and extensibility for advanced map features while aligning with Laravel's ORM patterns and job-based rendering workflow.
