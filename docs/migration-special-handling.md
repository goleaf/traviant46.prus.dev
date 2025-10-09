# Tables Requiring Special Handling During Migration

Some tables in the TravianT4.6 schema carry state that is easy to corrupt during a data migration. The following notes summarize what to double-check before copying data out of a live world and how to validate the migrated rows after the import.

## Users
* The `users` table stores password hashes in the `password` column and other security-sensitive flags such as sitter permissions and alliance roles.【F:main_script/include/schema/T4.4.sql†L1454-L1515】  Always confirm that the source system uses the same hashing algorithm (the field length indicates a 40-character SHA-1 style digest). If the new environment uses a different hashing scheme, schedule a re-hash step or force password resets.
* Validate related status flags like `email_verified`, sitter permissions, and `goldclub` after import, because stale flags can lock players out or change their privileges.【F:main_script/include/schema/T4.4.sql†L1465-L1514】

## Villages and World Coordinates
* Villages live in the `vdata` table, keyed by `kid` and tracking loyalty, expansion history, resource production, and other per-village timers.【F:main_script/include/schema/T4.4.sql†L1593-L1639】  Migrate this table in sync with `units`, `enforcement`, and other village-scoped data to keep ownership and troop counts consistent.
* Coordinate data is stored separately in `wdata`, which maps each `kid` to its `x`/`y` position and terrain configuration.【F:main_script/include/schema/T4.4.sql†L1688-L1706】  When changing map sizes or world seeds, regenerate these rows instead of copying them verbatim, then remap the `kid` references.

## Attacks and Movements in Flight
* The `movement` table records every in-flight troop action with start and end timestamps, troop payload, hero redeployment flags, and targeting metadata.【F:main_script/include/schema/T4.4.sql†L1072-L1108】  Freeze the game world (or drain the queue) before exporting so you do not miss new rows created during the dump, or plan to replay movements created during the migration window.
* If you cannot pause the world, capture the maximum `end_time` you exported and temporarily block new actions until all rows with an earlier `end_time` finish processing in the target environment.【F:main_script/include/schema/T4.4.sql†L1095-L1108】

## Building and Training Queues
* Building upgrades in progress are stored in `building_upgrade`, which tracks the building slot, master builder usage, and `commence` timestamps.【F:main_script/include/schema/T4.4.sql†L448-L458】  Migrating without adjusting these timestamps will restart or skip queued upgrades, so copy them with the exact same server time reference or recompute the delta relative to the new epoch.
* Troop training queues live in the `training` table, keyed by `kid`, troop type (`item_id`), counts, and `commence`/`end_time` markers.【F:main_script/include/schema/T4.4.sql†L1348-L1363】  Align the target server clock or recalculate the remaining training duration to avoid double-training or orphaned batches.

## Farmlist Automation Tables
* Farm lists (`farmlist`) define the target villages for a player's automated raids, including the owning player (`owner`), the originating village (`kid`), optional automation flags, and randomized send intervals (`randSec`).【F:main_script/include/schema/T4.4.sql†L547-L560】  Confirm that the destination village still exists and belongs to the same account after migration so queued dispatches do not point to deleted or transferred villages.
* The `raidlist` table stores every slot within a farm list, including the target `kid`, cached `distance`, and the exact troop counts (`u1`–`u10`) to dispatch.【F:main_script/include/schema/T4.4.sql†L1253-L1273】  Migrate this data in lockstep with `units` and `movement` to prevent inconsistencies between what the list plans to send and the troops actually stationed in the source village.
* `farmlist_last_reports` links players to the most recent report generated for a specific target, allowing the UI to highlight fresh battle outcomes.【F:main_script/include/schema/T4.4.sql†L1827-L1836】  When importing, keep report identifiers stable or remap them to the new `ndata` primary keys so players do not lose visibility into their latest farm runs.

## Recommended Migration Checklist
1. Pause the game world or place it in maintenance mode to block new movements and queue changes during the export.
2. Dump the schema and data for `users`, `vdata`, `wdata`, `movement`, `building_upgrade`, `training`, and their dependent tables in a single transaction.
3. Restore the dump in the new environment, adjusting timestamps if the server clock or speed differs.
4. Run integrity checks: attempt login with a test account, inspect a village's coordinates/resources, and verify that an in-flight attack completes at the expected time.
