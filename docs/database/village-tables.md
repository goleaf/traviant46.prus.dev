# Legacy Village Tables Reference

This document captures the legacy Travian T4.4 schema for village-centric tables to aid the Laravel 12 migration.
Each section summarizes purpose, notable columns, and indexing straight from the original `main_script/include/schema/T4.4.sql` DDL.

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

## `demolition`
- Handles queued tear-down jobs per village slot with completion timestamp and status flag. 【F:main_script/include/schema/T4.4.sql†L473-L480】
- Uses a composite primary key on job `id` and `kid` to avoid duplicate entries during cascading deletes. 【F:main_script/include/schema/T4.4.sql†L475-L481】

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

## `building_catalog`
- Laravel-native lookup table that documents each constructed building type, its prerequisite structures, and any per-level bonuses that impact production or storage. 【F:database/migrations/2025_10_26_224821_create_building_catalog_table.php†L16-L30】
- Seeded from `config/building_catalog.php`, which enumerates Sawmill, Grain Mill, Iron Foundry, Bakery, Warehouse, and Granary definitions including prerequisite levels and either production percentage gains or storage capacity curves. 【F:config/building_catalog.php†L1-L85】
- Linked to `building_types` so the seeder can cross reference GID-aligned type records before materialising catalog rows. 【F:database/seeders/BuildingCatalogSeeder.php†L20-L37】

## Migration considerations
- These tables rely heavily on implicit foreign keys (`owner`, `kid`, `did`) that need explicit relationships when ported to Laravel's schema. Capturing these dependencies up front helps design normalized replacements like `villages`, `village_buildings`, `map_tiles`, and `oases`. 【F:main_script/include/schema/T4.4.sql†L1215-L1229】【F:main_script/include/schema/T4.4.sql†L1595-L1636】
