# Legacy Village Tables Reference

This document captures the legacy Travian T4.4 schema for village-centric tables to aid the Laravel 12 migration.
Each section summarizes purpose, notable columns, and indexing straight from the original `main_script/include/schema/T4.4.sql` DDL.

## Laravel migration counterpart
- The canonical Laravel table lives in `2025_12_05_000100_create_villages_table.php` and codifies the new normalized schema with strict foreign keys to `worlds` and `users`. 【F:database/migrations/2025_12_05_000100_create_villages_table.php†L1-L47】
- Columns `x` and `y` store Cartesian coordinates (replacing legacy `kid` arithmetic) while `is_capital`, `population`, `loyalty`, and `culture_points` provide the baseline progress metrics required by downstream migrations. 【F:database/migrations/2025_12_05_000100_create_villages_table.php†L29-L42】

## `vdata`
- Stores one row per village keyed by `kid`, tracking ownership (`owner`), basic metadata (`name`, `fieldtype`, `capital`, `type`), and population/culture points. 【F:main_script/include/schema/T4.4.sql†L1593-L1606】
- Contains live resource stock (`wood`, `clay`, `iron`, `crop`), production rates (`woodp`, `clayp`, `ironp`, `cropp`), and storage caps (`maxstore`, `extraMaxstore`, `maxcrop`, `extraMaxcrop`) used for hourly resource calculations. 【F:main_script/include/schema/T4.4.sql†L1605-L1616】
- Tracks upkeep, loyalty, creation timestamps, and wonder/farm/artifact flags that determine special behaviors. 【F:main_script/include/schema/T4.4.sql†L1617-L1627】
- Includes expansion bookkeeping (`expandedfrom`) and version counters for troop/movement caches plus an optional watcher (`checker`). 【F:main_script/include/schema/T4.4.sql†L1628-L1632】

## `fdata`
- Holds the 40 village building slots (`f1`–`f40`) alongside building type identifiers (`f1t`–`f40t`) for each village `kid`. 【F:main_script/include/schema/T4.4.sql†L563-L645】
- Adds special columns for wonder villages (`f99`, `f99t`, `lastWWUpgrade`, `wwname`) and embassy/hero mansion levels that gate alliance features. 【F:main_script/include/schema/T4.4.sql†L646-L653】

## `wdata`
- Represents every world tile with coordinates (`x`, `y`), base field type, oasis attributes, and landscape variant. 【F:main_script/include/schema/T4.4.sql†L1688-L1696】
- Stores crop bonus percentage, occupancy flag, and a serialized `map` string used by the legacy renderer. 【F:main_script/include/schema/T4.4.sql†L1696-L1698】
- Indexed on crop percentages, field type, oasis type, and occupancy to accelerate map queries. 【F:main_script/include/schema/T4.4.sql†L1699-L1703】

## `available_villages`
- Queue of spawnable tiles with polar coordinates (`r`, `angle`) and randomization seed (`rand`) for assigning new player villages. 【F:main_script/include/schema/T4.4.sql†L1710-L1717】
- Tracks whether the candidate tile has been used via the `occupied` flag and keeps helper indexes on all selection fields. 【F:main_script/include/schema/T4.4.sql†L1717-L1723】

## `odata`
- Stores oasis data keyed by map `kid`, including oasis type, linked owner village (`did`), resource stock, and production ticks. 【F:main_script/include/schema/T4.4.sql†L1213-L1223】
- Tracks loyalty, last conquest time, and current owner for oases that can be annexed by players. 【F:main_script/include/schema/T4.4.sql†L1224-L1228】
- Indexes type, owner, and the attached village for faster oasis assignment queries. 【F:main_script/include/schema/T4.4.sql†L1229-L1233】

## `building_upgrade`
- Active upgrade queue per village storing slot (`building_field`), master builder flag (`isMaster`), and timing fields (`start_time`, `commence`). 【F:main_script/include/schema/T4.4.sql†L448-L455】
- Indexed by slot/master/timing combination to pop the next upgrade efficiently. 【F:main_script/include/schema/T4.4.sql†L456-L458】

## `build_queues`
- Laravel-native table that tracks queued building upgrades per village with foreign keys, enum state machine (`pending`, `working`, `done`), and indexed completion timestamps.
- Replaces the legacy `building_upgrade` timing logic with explicit queue metadata stored in `database/migrations/2025_10_26_223347_create_build_queues_table.php`. 【F:database/migrations/2025_10_26_223347_create_build_queues_table.php†L1-L53】
- Integrates with `App\Models\Game\BuildQueue` and the queue processing jobs to shard workload by village ID.
## `resource_fields`
- New normalized table replacing the legacy `f1`–`f18` resource slots, associating each slot number with a resource `kind` (enum of wood, clay, iron, crop), a current `level`, and cached hourly production for fast lookups.
- Enforces a unique combination of `village_id`, `kind`, and `slot_number` so that migrations cannot create duplicate resource entries for a specific slot, while still allowing multiple fields of the same kind across different slots.
- Tied to `villages` through a cascading foreign key to keep rows in sync when players abandon or lose villages.

## `demolition`
- Handles queued tear-down jobs per village slot with completion timestamp and status flag. 【F:main_script/include/schema/T4.4.sql†L473-L480】
- Uses a composite primary key on job `id` and `kid` to avoid duplicate entries during cascading deletes. 【F:main_script/include/schema/T4.4.sql†L475-L481】

## `training_queues`
- Represents active troop training batches in the modernised schema, linking each row to the owning village (`village_id`) and the troop blueprint (`troop_type_id`). 【F:database/migrations/2025_10_26_223419_create_training_queues_table.php†L17-L22】
- Records the queued unit count, completion timestamp (`finishes_at`), and originating building reference (`building_ref`) while indexing `finishes_at` to make dequeuing efficient. 【F:database/migrations/2025_10_26_223419_create_training_queues_table.php†L22-L25】

## `smithy`
- Records equipment upgrade levels unlocked in the smithy for each unit type (`u1`–`u8`) per village `kid`. 【F:main_script/include/schema/T4.4.sql†L1313-L1323】

## `tdata`
- Tracks research unlock progress for troop types (`u2`–`u9`) per village `kid`, representing the academy state. 【F:main_script/include/schema/T4.4.sql†L1331-L1342】

## `research`
- Queue of active academy research jobs with unit identifier (`nr`), research mode, and finish time for a given village `kid`. 【F:main_script/include/schema/T4.4.sql†L1276-L1285】

## `traderoutes`
- Defines scheduled market shipments between villages, capturing origin/destination kids, resource payload, scheduling, and enablement state. 【F:main_script/include/schema/T4.4.sql†L895-L907】
- Indexed by origin village, enablement, and execution time to drive dispatch timers. 【F:main_script/include/schema/T4.4.sql†L908-L910】

## `building_upgrade` / `demolition` dependencies
- Both queues depend on `kid` values present in `vdata` and building slot identifiers from `fdata`, ensuring upgrades and demolitions align with the village layout. 【F:main_script/include/schema/T4.4.sql†L448-L480】【F:main_script/include/schema/T4.4.sql†L563-L645】【F:main_script/include/schema/T4.4.sql†L1595-L1632】

## Migration considerations
- These tables rely heavily on implicit foreign keys (`owner`, `kid`, `did`) that need explicit relationships when ported to Laravel's schema. Capturing these dependencies up front helps design normalized replacements like `villages`, `village_buildings`, `map_tiles`, and `oases`. 【F:main_script/include/schema/T4.4.sql†L1215-L1229】【F:main_script/include/schema/T4.4.sql†L1595-L1636】
- The normalized `buildings` table in Laravel stores each constructed structure with `village_id`, `building_type`, `position`, and `level`, plus an index on (`village_id`, `building_type`) to support filtered lookups when synchronising legacy `fdata` rows. 【F:database/migrations/2025_10_26_223332_create_buildings_table.php†L13-L38】

## `map_tiles` (Laravel)
- Normalised replacement for the legacy `wdata` map, storing one row per world coordinate with enforced uniqueness across `(world_id, x, y)` so Livewire map components can query deterministic records. 【F:database/migrations/2025_12_15_000000_create_map_tiles_table.php†L1-L36】
- Persists terrain metadata via `tile_type` and `resource_pattern` columns while tracking optional oasis information in the nullable `oasis_type` field. 【F:database/migrations/2025_12_15_000000_create_map_tiles_table.php†L18-L28】
- Adds covering indexes on `world_id`, `tile_type`, and `oasis_type` to mirror the high-frequency queries used by the map overview and crop finder tools. 【F:database/migrations/2025_12_15_000000_create_map_tiles_table.php†L30-L32】
## Laravel replacements
- The new `oases` table anchors every conquerable oasis to a world row and coordinate pair while persisting respawn scheduling metadata and serialized nature garrisons. 【F:database/migrations/2025_03_01_000070_create_oases_table.php†L17-L40】
- The `oasis_ownerships` pivot keeps the village-to-oasis relationship normalized with cascading deletes when a village is removed, mirroring the legacy `odata.did` behavior with explicit foreign keys. 【F:database/migrations/2025_03_01_000080_create_oasis_ownerships_table.php†L17-L27】
