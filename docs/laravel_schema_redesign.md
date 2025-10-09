# Laravel Schema Redesign Plan

## Conventions Alignment
- Convert all identifiers to `snake_case`, preserve lowercase table names, and adopt Laravel default primary keys (`id` BIGINT UNSIGNED auto-increment) and timestamp columns (`created_at`, `updated_at`) on mutable entities.
- Replace ad-hoc integer timestamps with proper `timestamp`/`datetime` columns using Laravel's timezone handling.
- Standardize boolean flags to `tinyint(1)` or Laravel's `boolean` casting, and normalize comma-delimited preference columns into relational tables where necessary.
- Add foreign key constraints for every relationship currently implied by integer columns (e.g., `uid`, `kid`, `aid`) and cascade updates/deletes according to gameplay rules.

## Core Entity Mapping

### Users & Authentication
- **Current state:** The `users` table blends account credentials, alliance state, profile text, preferences, and statistics in a single row.【F:main_script/include/schema/T4.4.sql†L1453-L1576】 Separate table `activation` stores registration tokens independently.【F:main_script/include/schema/T4.4.sql†L109-L126】
- **Proposed tables:**
  - `users`: retain identity (`id`, `name`, `email`, `race`, `access`) but adopt Laravel columns (`email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`). Migrate `signupTime`, `last_login_time`, and `last_owner_login_time` into proper timestamps (`signed_up_at`, `last_login_at`, etc.). Introduce `alliance_id` foreign key replacing `aid`.
  - `user_profiles`: move profile content (`desc1`, `desc2`, `note`), demographic fields, and display options.
  - `user_statistics`: track rolling totals (attack/defense points, contributions) with timestamps for weekly resets.
  - `user_settings`: normalize preference strings (e.g., `favorTabs`, `reportFilters`, `allianceSettings`) into structured JSON or related tables.
  - `user_sitters`: pivot table covering sitter relationships (`user_id`, `sitter_user_id`, `permissions`, `slot`).
- **Activation flow:** Merge `activation` into `email_verifications` tied to `user_id`, storing token, referring user, sent/claimed timestamps, and reminder counters. Alternatively, leverage Laravel's built-in email verification timestamps and store outstanding tokens in `password_reset_tokens`.

### Attacks & Movements
- **Current state:** Table `a2b` holds outgoing attack payloads with timestamp checksum and per-unit counts, while `movement` mixes movement metadata with duplicated troop columns and attack modes.【F:main_script/include/schema/T4.4.sql†L1-L24】【F:main_script/include/schema/T4.4.sql†L1072-L1109】
- **Proposed tables:**
  - `attacks`: replace `a2b`, capturing `origin_village_id`, `target_village_id`, `launched_at`, `arrival_at`, `attack_type`, `is_hero_redeploy`, and status flags.
  - `attack_units`: child table storing counts per unit type using `unit_type_id` rather than wide `u1`…`u11` columns.
  - `unit_movements`: refactor `movement` for all troop transfers, linking to `attacks` when applicable and referencing `movement_units` child records for counts.
  - `movement_marks`: normalize `markState` data (for UI markers) with foreign keys.

### Villages & World Map
- **Current state:** `vdata` stores village economy, loyalty, creation time, and flags (capital, wonder, farm) per `kid` key.【F:main_script/include/schema/T4.4.sql†L1592-L1633】 `wdata` manages map tiles with coordinates and occupancy flags.【F:main_script/include/schema/T4.4.sql†L1688-L1704】 Additional spatial tables include `available_villages` and `oases` data elsewhere.
- **Proposed tables:**
  - `villages`: rename `vdata`, converting resource fields to dedicated production tables, tracking `owner_id`, `capital`, `population`, `loyalty`, `created_at`, and `last_loyalty_update_at` timestamps.
  - `village_resources`: move dynamic stock (`wood`, `clay`, `iron`, `crop`) and capacities (`max_store`, `max_crop`, extra storage bonuses) into a related table with update timestamps.
  - `village_flags`: hold booleans like `is_world_wonder`, `is_farm`, `is_artifact`, and `evasion_enabled` to simplify indexing.
  - `map_tiles`: rename `wdata`, with columns `x`, `y`, `field_type`, `oasis_type`, `landscape_type`, `crop_percent`, and `is_occupied`; link to `villages` when occupied.
  - `available_villages`: becomes `spawn_candidates` referencing `map_tile_id` instead of duplicating coordinates.

### Buildings & Upgrades
- **Current state:** `fdata` keeps 40 field slots plus wonder metadata in a single row per village.【F:main_script/include/schema/T4.4.sql†L562-L654】 Supporting upgrade queues appear in `building_upgrade` and `construction` tables elsewhere.
- **Proposed tables:**
  - `village_buildings`: each row defines `village_id`, `slot_number`, `building_type_id`, `level`, with timestamps for last upgrade.
  - `village_wonder_state`: isolate world wonder tracking (`lastWWUpgrade`, `wwname`).
  - `building_upgrade_queue`: align existing queue tables with Laravel naming (`village_building_upgrades`) and foreign keys to `villages` and `village_buildings`.

### Units & Training
- **Current state:** `units` stores stationed troops per village using wide columns, while `training`, `smithy`, `tdata`, and `movement` repeat unit columns for upgrades and queues.【F:main_script/include/schema/T4.4.sql†L1392-L1409】【F:main_script/include/schema/T4.4.sql†L1330-L1363】
- **Proposed tables:**
  - `village_units`: pivot of `village_id`, `unit_type_id`, `quantity`, separating home troops from prisoners via status column.
  - `unit_training_queue`: rename `training`, linking to `village_id`, `unit_type_id`, queue position, `starts_at`, `ends_at`, and consumption metadata.
  - `unit_research` and `unit_upgrade_levels`: replace `tdata` and `smithy`, storing per-unit unlock and upgrade levels with normalized rows.
  - `unit_movements` / `movement_units`: as above, standardize all troop movement counts.

### Alliances & Social Systems
- **Current state:** Alliance data spans `alidata`, `ali_invite`, `ali_log`, `alistats`, `allimedal`, `alliance_notification`, and forum tables, all referencing alliance IDs and user IDs without foreign keys.【F:main_script/include/schema/T4.4.sql†L176-L256】【F:main_script/include/schema/T4.4.sql†L258-L314】【F:main_script/include/schema/T4.4.sql†L321-L356】【F:main_script/include/schema/T4.4.sql†L1115-L1127】
- **Proposed tables:**
  - `alliances`: rename `alidata`, converting descriptive fields to text columns and adding `created_at`, `updated_at`.
  - `alliance_members`: pivot table linking `alliance_id` and `user_id` with role references and join timestamps; stores contribution totals or references to `user_statistics`.
  - `alliance_roles`: dictionary for customizable permissions, replacing numeric `alliance_role` values in `users`.
  - `alliance_invitations`: rename `ali_invite`, referencing inviter, invitee, target alliance, and status timestamps.
  - `alliance_logs`, `alliance_statistics`, `alliance_medals`, `alliance_notifications`: rename respective tables and attach foreign keys.
  - `alliance_forums`, `alliance_forum_topics`, `alliance_forum_posts`: restructure existing forum tables to leverage Laravel polymorphic discussion models.

### Supporting Mechanics
- Normalize repetitive `uid`/`kid` references in quest, hero, market, trade route, and messaging tables by adding foreign keys and timestamps.
- Introduce pivot tables for many-to-many relationships such as `friendlist`, `ignore_list`, and `map_mark` participants.
- Replace `varchar`-encoded arrays (e.g., `bounty`, `data`, `map`) with JSON columns or child tables for structured access.

## Relationship Overview
- `users` ↔ `alliances`: many-to-many via `alliance_members` with optional role pivot.
- `users` ↔ `villages`: one-to-many (`users.id` → `villages.owner_id`) with home village pointer stored on users.
- `villages` ↔ `village_buildings` / `village_units` / `village_resources`: one-to-many, all enforcing cascading deletes on village removal.
- `attacks` / `unit_movements` connect origin/target villages and optionally alliances for coordinated actions.
- Alliance subsystems (forums, logs, notifications) reference `alliances` and `users` with cascade delete/soft-delete strategies to keep history.

## Migration Considerations
- Migrate data in phases: create new normalized tables alongside legacy ones, populate via scripts, then cut over application code.
- Use Laravel migrations to define schema, ensuring indexes cover high-frequency queries (e.g., `movement` end times, alliance rankings).
- Apply enum tables or configuration-driven dictionaries for unit types, building types, and quest identifiers to replace implicit numbering.
- Introduce soft deletes (`deleted_at`) where gameplay requires recoverability (messages, logs) while keeping archival tables for audit history.
