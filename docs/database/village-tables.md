# Legacy Village Tables Reference

This document captures the legacy Travian T4.4 schema for village-centric tables to aid the Laravel 12 migration.
Each section summarizes purpose, notable columns, and indexing straight from the original `_travian/main_script/include/schema/T4.4.sql` DDL.

## `vdata`
- Stores one row per village keyed by `kid`, tracking ownership (`owner`), basic metadata (`name`, `fieldtype`, `capital`, `type`), and population/culture points. 【F:_travian/main_script/include/schema/T4.4.sql†L1593-L1606】
- Contains live resource stock (`wood`, `clay`, `iron`, `crop`), production rates (`woodp`, `clayp`, `ironp`, `cropp`), and storage caps (`maxstore`, `extraMaxstore`, `maxcrop`, `extraMaxcrop`) used for hourly resource calculations. 【F:_travian/main_script/include/schema/T4.4.sql†L1605-L1616】
- Tracks upkeep, loyalty, creation timestamps, and wonder/farm/artifact flags that determine special behaviors. 【F:_travian/main_script/include/schema/T4.4.sql†L1617-L1627】
- Includes expansion bookkeeping (`expandedfrom`) and version counters for troop/movement caches plus an optional watcher (`checker`). 【F:_travian/main_script/include/schema/T4.4.sql†L1628-L1632】

## `fdata`
- Holds the 40 village building slots (`f1`–`f40`) alongside building type identifiers (`f1t`–`f40t`) for each village `kid`. 【F:_travian/main_script/include/schema/T4.4.sql†L563-L645】
- Adds special columns for wonder villages (`f99`, `f99t`, `lastWWUpgrade`, `wwname`) and embassy/hero mansion levels that gate alliance features. 【F:_travian/main_script/include/schema/T4.4.sql†L646-L653】

## `wdata`
- Represents every world tile with coordinates (`x`, `y`), base field type, oasis attributes, and landscape variant. 【F:_travian/main_script/include/schema/T4.4.sql†L1688-L1696】
- Stores crop bonus percentage, occupancy flag, and a serialized `map` string used by the legacy renderer. 【F:_travian/main_script/include/schema/T4.4.sql†L1696-L1698】
- Indexed on crop percentages, field type, oasis type, and occupancy to accelerate map queries. 【F:_travian/main_script/include/schema/T4.4.sql†L1699-L1703】

## `available_villages`
- Queue of spawnable tiles with polar coordinates (`r`, `angle`) and randomization seed (`rand`) for assigning new player villages. 【F:_travian/main_script/include/schema/T4.4.sql†L1710-L1717】
- Tracks whether the candidate tile has been used via the `occupied` flag and keeps helper indexes on all selection fields. 【F:_travian/main_script/include/schema/T4.4.sql†L1717-L1723】

## `odata`
- Stores oasis data keyed by map `kid`, including oasis type, linked owner village (`did`), resource stock, and production ticks. 【F:_travian/main_script/include/schema/T4.4.sql†L1213-L1223】
- Tracks loyalty, last conquest time, and current owner for oases that can be annexed by players. 【F:_travian/main_script/include/schema/T4.4.sql†L1224-L1228】
- Indexes type, owner, and the attached village for faster oasis assignment queries. 【F:_travian/main_script/include/schema/T4.4.sql†L1229-L1233】

## `building_upgrade`
- Active upgrade queue per village storing slot (`building_field`), master builder flag (`isMaster`), and timing fields (`start_time`, `commence`). 【F:_travian/main_script/include/schema/T4.4.sql†L448-L455】
- Indexed by slot/master/timing combination to pop the next upgrade efficiently. 【F:_travian/main_script/include/schema/T4.4.sql†L456-L458】

## `demolition`
- Handles queued tear-down jobs per village slot with completion timestamp and status flag. 【F:_travian/main_script/include/schema/T4.4.sql†L473-L480】
- Uses a composite primary key on job `id` and `kid` to avoid duplicate entries during cascading deletes. 【F:_travian/main_script/include/schema/T4.4.sql†L475-L481】

## `smithy`
- Records equipment upgrade levels unlocked in the smithy for each unit type (`u1`–`u8`) per village `kid`. 【F:_travian/main_script/include/schema/T4.4.sql†L1313-L1323】

## `tdata`
- Tracks research unlock progress for troop types (`u2`–`u9`) per village `kid`, representing the academy state. 【F:_travian/main_script/include/schema/T4.4.sql†L1331-L1342】

## `research`
- Queue of active academy research jobs with unit identifier (`nr`), research mode, and finish time for a given village `kid`. 【F:_travian/main_script/include/schema/T4.4.sql†L1276-L1285】

## `traderoutes`
- Defines scheduled market shipments between villages, capturing origin/destination kids, resource payload, scheduling, and enablement state. 【F:_travian/main_script/include/schema/T4.4.sql†L895-L907】
- Indexed by origin village, enablement, and execution time to drive dispatch timers. 【F:_travian/main_script/include/schema/T4.4.sql†L908-L910】

## `building_upgrade` / `demolition` dependencies
- Both queues depend on `kid` values present in `vdata` and building slot identifiers from `fdata`, ensuring upgrades and demolitions align with the village layout. 【F:_travian/main_script/include/schema/T4.4.sql†L448-L480】【F:_travian/main_script/include/schema/T4.4.sql†L563-L645】【F:_travian/main_script/include/schema/T4.4.sql†L1595-L1632】

## Migration considerations
- These tables rely heavily on implicit foreign keys (`owner`, `kid`, `did`) that need explicit relationships when ported to Laravel's schema. Capturing these dependencies up front helps design normalized replacements like `villages`, `village_buildings`, `map_tiles`, and `oases`. 【F:_travian/main_script/include/schema/T4.4.sql†L1215-L1229】【F:_travian/main_script/include/schema/T4.4.sql†L1595-L1636】
