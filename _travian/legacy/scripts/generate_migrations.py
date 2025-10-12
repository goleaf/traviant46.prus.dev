import os
from textwrap import indent

BASE_PATH = os.path.join(os.path.dirname(__file__), '..', 'database', 'migrations')
BASE_TIMESTAMP = '2024_07_01'
SEQUENCE_STEP = 100

entries = [
    # Core tables
    {
        "slug": "create_core_user_profiles_table",
        "table": "core_user_profiles",
        "fields": [
            "$table->foreignId('user_id')->constrained('users');",
            "$table->string('profile_name');",
            "$table->text('biography')->nullable();",
            "$table->json('settings')->nullable();",
            "$table->unique(['user_id', 'profile_name']);",
        ],
    },
    {
        "slug": "create_core_user_preferences_table",
        "table": "core_user_preferences",
        "fields": [
            "$table->foreignId('user_id')->constrained('users');",
            "$table->string('preference_key');",
            "$table->text('preference_value')->nullable();",
            "$table->boolean('is_locked')->default(false);",
            "$table->unique(['user_id', 'preference_key']);",
        ],
    },
    {
        "slug": "create_core_user_statistics_table",
        "table": "core_user_statistics",
        "fields": [
            "$table->foreignId('user_id')->constrained('users');",
            "$table->string('metric');",
            "$table->bigInteger('value')->default(0);",
            "$table->date('captured_on');",
            "$table->unique(['user_id', 'metric', 'captured_on']);",
        ],
    },
    {
        "slug": "create_core_user_login_histories_table",
        "table": "core_user_login_histories",
        "fields": [
            "$table->foreignId('user_id')->constrained('users');",
            "$table->string('ip_address', 64);",
            "$table->string('user_agent')->nullable();",
            "$table->timestamp('logged_in_at');",
        ],
    },
    {
        "slug": "create_core_user_security_settings_table",
        "table": "core_user_security_settings",
        "fields": [
            "$table->foreignId('user_id')->constrained('users');",
            "$table->boolean('two_factor_enabled')->default(false);",
            "$table->string('recovery_code')->nullable();",
            "$table->timestamp('last_password_change')->nullable();",
        ],
    },
    {
        "slug": "create_core_user_devices_table",
        "table": "core_user_devices",
        "fields": [
            "$table->foreignId('user_id')->constrained('users');",
            "$table->string('device_identifier');",
            "$table->string('device_type', 32);",
            "$table->timestamp('last_seen_at')->nullable();",
            "$table->unique(['user_id', 'device_identifier']);",
        ],
    },
    {
        "slug": "create_core_user_sessions_table",
        "table": "core_user_sessions",
        "fields": [
            "$table->foreignId('user_id')->constrained('users');",
            "$table->string('session_token', 128)->unique();",
            "$table->timestamp('expires_at');",
            "$table->boolean('is_active')->default(true);",
        ],
    },
    {
        "slug": "create_core_config_categories_table",
        "table": "core_config_categories",
        "fields": [
            "$table->string('slug')->unique();",
            "$table->string('name');",
            "$table->text('description')->nullable();",
        ],
    },
    {
        "slug": "create_core_config_settings_table",
        "table": "core_config_settings",
        "fields": [
            "$table->foreignId('category_id')->constrained('core_config_categories');",
            "$table->string('setting_key')->unique();",
            "$table->text('setting_value')->nullable();",
            "$table->boolean('is_encrypted')->default(false);",
        ],
    },
    {
        "slug": "create_core_config_logs_table",
        "table": "core_config_logs",
        "fields": [
            "$table->foreignId('setting_id')->constrained('core_config_settings');",
            "$table->foreignId('user_id')->nullable()->constrained('users');",
            "$table->string('action');",
            "$table->json('changes')->nullable();",
        ],
    },
    {
        "slug": "create_core_summary_snapshots_table",
        "table": "core_summary_snapshots",
        "fields": [
            "$table->dateTime('captured_at');",
            "$table->string('scope');",
            "$table->json('payload');",
            "$table->unique(['captured_at', 'scope']);",
        ],
    },
    {
        "slug": "create_core_summary_metrics_table",
        "table": "core_summary_metrics",
        "fields": [
            "$table->foreignId('snapshot_id')->constrained('core_summary_snapshots');",
            "$table->string('metric');",
            "$table->bigInteger('value')->default(0);",
            "$table->unique(['snapshot_id', 'metric']);",
        ],
    },
    {
        "slug": "create_core_summary_trends_table",
        "table": "core_summary_trends",
        "fields": [
            "$table->string('metric');",
            "$table->date('recorded_on');",
            "$table->decimal('average', 12, 2);",
            "$table->decimal('change', 8, 2)->default(0);",
            "$table->unique(['metric', 'recorded_on']);",
        ],
    },

    # Game world tables
    {
        "slug": "create_wdata_worlds_table",
        "table": "wdata_worlds",
        "fields": [
            "$table->string('code')->unique();",
            "$table->string('name');",
            "$table->unsignedInteger('speed')->default(1);",
            "$table->date('started_on');",
        ],
    },
    {
        "slug": "create_wdata_regions_table",
        "table": "wdata_regions",
        "fields": [
            "$table->foreignId('world_id')->constrained('wdata_worlds');",
            "$table->string('region_code');",
            "$table->string('region_name');",
            "$table->unique(['world_id', 'region_code']);",
        ],
    },
    {
        "slug": "create_wdata_tiles_table",
        "table": "wdata_tiles",
        "fields": [
            "$table->foreignId('region_id')->constrained('wdata_regions');",
            "$table->unsignedInteger('x_coordinate');",
            "$table->unsignedInteger('y_coordinate');",
            "$table->string('terrain_type');",
            "$table->unique(['region_id', 'x_coordinate', 'y_coordinate']);",
        ],
    },
    {
        "slug": "create_wdata_tiles_resources_table",
        "table": "wdata_tiles_resources",
        "fields": [
            "$table->foreignId('tile_id')->constrained('wdata_tiles');",
            "$table->unsignedInteger('wood');",
            "$table->unsignedInteger('clay');",
            "$table->unsignedInteger('iron');",
            "$table->unsignedInteger('crop');",
        ],
    },
    {
        "slug": "create_wdata_season_configs_table",
        "table": "wdata_season_configs",
        "fields": [
            "$table->foreignId('world_id')->constrained('wdata_worlds');",
            "$table->string('season_name');",
            "$table->date('season_start');",
            "$table->date('season_end')->nullable();",
            "$table->json('rules')->nullable();",
        ],
    },
    {
        "slug": "create_vdata_villages_table",
        "table": "vdata_villages",
        "fields": [
            "$table->foreignId('world_id')->constrained('wdata_worlds');",
            "$table->foreignId('tile_id')->unique()->constrained('wdata_tiles');",
            "$table->foreignId('owner_id')->nullable()->constrained('users');",
            "$table->string('village_name');",
            "$table->unsignedInteger('population')->default(0);",
        ],
    },
    {
        "slug": "create_vdata_village_snapshots_table",
        "table": "vdata_village_snapshots",
        "fields": [
            "$table->foreignId('village_id')->constrained('vdata_villages');",
            "$table->dateTime('captured_at');",
            "$table->unsignedInteger('population')->default(0);",
            "$table->json('resources')->nullable();",
            "$table->unique(['village_id', 'captured_at']);",
        ],
    },
    {
        "slug": "create_vdata_village_statistics_table",
        "table": "vdata_village_statistics",
        "fields": [
            "$table->foreignId('village_id')->constrained('vdata_villages');",
            "$table->string('metric');",
            "$table->bigInteger('value')->default(0);",
            "$table->date('recorded_on');",
            "$table->unique(['village_id', 'metric', 'recorded_on']);",
        ],
    },
    {
        "slug": "create_vdata_village_events_table",
        "table": "vdata_village_events",
        "fields": [
            "$table->foreignId('village_id')->constrained('vdata_villages');",
            "$table->string('event_type');",
            "$table->json('payload')->nullable();",
            "$table->dateTime('occurred_at');",
            "$table->index(['village_id', 'occurred_at']);",
        ],
    },
    {
        "slug": "create_vdata_population_rankings_table",
        "table": "vdata_population_rankings",
        "fields": [
            "$table->foreignId('world_id')->constrained('wdata_worlds');",
            "$table->foreignId('village_id')->constrained('vdata_villages');",
            "$table->unsignedInteger('rank');",
            "$table->date('ranking_date');",
            "$table->unique(['world_id', 'ranking_date', 'village_id']);",
        ],
    },
    {
        "slug": "create_odata_oases_table",
        "table": "odata_oases",
        "fields": [
            "$table->foreignId('world_id')->constrained('wdata_worlds');",
            "$table->foreignId('tile_id')->unique()->constrained('wdata_tiles');",
            "$table->string('oasis_type');",
            "$table->boolean('is_occupied')->default(false);",
        ],
    },
    {
        "slug": "create_odata_oasis_resources_table",
        "table": "odata_oasis_resources",
        "fields": [
            "$table->foreignId('oasis_id')->constrained('odata_oases');",
            "$table->unsignedInteger('wood');",
            "$table->unsignedInteger('clay');",
            "$table->unsignedInteger('iron');",
            "$table->unsignedInteger('crop');",
        ],
    },
    {
        "slug": "create_odata_oasis_owners_table",
        "table": "odata_oasis_owners",
        "fields": [
            "$table->foreignId('oasis_id')->constrained('odata_oases');",
            "$table->foreignId('owner_id')->nullable()->constrained('users');",
            "$table->foreignId('village_id')->nullable()->constrained('vdata_villages');",
            "$table->dateTime('claimed_at')->nullable();",
            "$table->dateTime('released_at')->nullable();",
        ],
    },
    {
        "slug": "create_odata_oasis_history_table",
        "table": "odata_oasis_history",
        "fields": [
            "$table->foreignId('oasis_id')->constrained('odata_oases');",
            "$table->string('event_type');",
            "$table->json('details')->nullable();",
            "$table->dateTime('recorded_at');",
            "$table->index(['oasis_id', 'recorded_at']);",
        ],
    },
    {
        "slug": "create_odata_oasis_rewards_table",
        "table": "odata_oasis_rewards",
        "fields": [
            "$table->foreignId('oasis_id')->constrained('odata_oases');",
            "$table->string('reward_type');",
            "$table->unsignedInteger('value')->default(0);",
            "$table->string('frequency')->default('daily');",
        ],
    },

    # Village-dependent tables
    {
        "slug": "create_fdata_fields_table",
        "table": "fdata_fields",
        "fields": [
            "$table->foreignId('village_id')->constrained('vdata_villages');",
            "$table->unsignedTinyInteger('slot');",
            "$table->string('field_type');",
            "$table->unsignedTinyInteger('level')->default(0);",
            "$table->unique(['village_id', 'slot']);",
        ],
    },
    {
        "slug": "create_fdata_field_levels_table",
        "table": "fdata_field_levels",
        "fields": [
            "$table->foreignId('field_id')->constrained('fdata_fields');",
            "$table->unsignedTinyInteger('level');",
            "$table->unsignedInteger('production')->default(0);",
            "$table->json('costs')->nullable();",
            "$table->unique(['field_id', 'level']);",
        ],
    },
    {
        "slug": "create_fdata_field_upgrades_table",
        "table": "fdata_field_upgrades",
        "fields": [
            "$table->foreignId('field_id')->constrained('fdata_fields');",
            "$table->unsignedTinyInteger('target_level');",
            "$table->dateTime('started_at');",
            "$table->dateTime('completed_at')->nullable();",
            "$table->foreignId('queued_by')->nullable()->constrained('users');",
        ],
    },
    {
        "slug": "create_units_infantry_table",
        "table": "units_infantry",
        "fields": [
            "$table->foreignId('village_id')->constrained('vdata_villages');",
            "$table->string('unit_type');",
            "$table->unsignedInteger('current')->default(0);",
            "$table->unsignedInteger('training')->default(0);",
            "$table->unique(['village_id', 'unit_type']);",
        ],
    },
    {
        "slug": "create_units_cavalry_table",
        "table": "units_cavalry",
        "fields": [
            "$table->foreignId('village_id')->constrained('vdata_villages');",
            "$table->string('unit_type');",
            "$table->unsignedInteger('current')->default(0);",
            "$table->unsignedInteger('training')->default(0);",
            "$table->unique(['village_id', 'unit_type']);",
        ],
    },
    {
        "slug": "create_units_siege_table",
        "table": "units_siege",
        "fields": [
            "$table->foreignId('village_id')->constrained('vdata_villages');",
            "$table->string('unit_type');",
            "$table->unsignedInteger('current')->default(0);",
            "$table->unsignedInteger('training')->default(0);",
            "$table->unique(['village_id', 'unit_type']);",
        ],
    },
    {
        "slug": "create_units_training_queue_table",
        "table": "units_training_queue",
        "fields": [
            "$table->foreignId('village_id')->constrained('vdata_villages');",
            "$table->string('unit_category');",
            "$table->unsignedInteger('amount');",
            "$table->dateTime('finishes_at');",
        ],
    },
    {
        "slug": "create_units_garrisons_table",
        "table": "units_garrisons",
        "fields": [
            "$table->foreignId('origin_village_id')->constrained('vdata_villages');",
            "$table->foreignId('target_village_id')->constrained('vdata_villages');",
            "$table->string('unit_type');",
            "$table->unsignedInteger('amount')->default(0);",
        ],
    },
    {
        "slug": "create_units_casualties_table",
        "table": "units_casualties",
        "fields": [
            "$table->foreignId('village_id')->constrained('vdata_villages');",
            "$table->string('unit_type');",
            "$table->unsignedInteger('lost')->default(0);",
            "$table->dateTime('recorded_at');",
            "$table->index(['village_id', 'recorded_at']);",
        ],
    },
    {
        "slug": "create_units_equipment_table",
        "table": "units_equipment",
        "fields": [
            "$table->foreignId('village_id')->constrained('vdata_villages');",
            "$table->string('equipment_type');",
            "$table->unsignedInteger('quantity')->default(0);",
            "$table->json('attributes')->nullable();",
            "$table->unique(['village_id', 'equipment_type']);",
        ],
    },
    {
        "slug": "create_hero_profiles_table",
        "table": "hero_profiles",
        "fields": [
            "$table->foreignId('user_id')->constrained('users');",
            "$table->string('hero_name');",
            "$table->unsignedInteger('level')->default(0);",
            "$table->unsignedInteger('experience')->default(0);",
        ],
    },
    {
        "slug": "create_hero_attributes_table",
        "table": "hero_attributes",
        "fields": [
            "$table->foreignId('hero_id')->constrained('hero_profiles');",
            "$table->string('attribute');",
            "$table->integer('value')->default(0);",
            "$table->boolean('is_primary')->default(false);",
            "$table->unique(['hero_id', 'attribute']);",
        ],
    },
    {
        "slug": "create_hero_experiences_table",
        "table": "hero_experiences",
        "fields": [
            "$table->foreignId('hero_id')->constrained('hero_profiles');",
            "$table->unsignedInteger('gained_experience');",
            "$table->string('source');",
            "$table->dateTime('gained_at');",
        ],
    },
    {
        "slug": "create_hero_skills_table",
        "table": "hero_skills",
        "fields": [
            "$table->foreignId('hero_id')->constrained('hero_profiles');",
            "$table->string('skill_name');",
            "$table->unsignedTinyInteger('points')->default(0);",
            "$table->boolean('is_active')->default(true);",
            "$table->unique(['hero_id', 'skill_name']);",
        ],
    },
    {
        "slug": "create_hero_treasury_table",
        "table": "hero_treasury",
        "fields": [
            "$table->foreignId('hero_id')->constrained('hero_profiles');",
            "$table->string('artifact_code');",
            "$table->unsignedInteger('power')->default(0);",
            "$table->dateTime('acquired_at');",
            "$table->unique(['hero_id', 'artifact_code']);",
        ],
    },
    {
        "slug": "create_hero_adventure_logs_table",
        "table": "hero_adventure_logs",
        "fields": [
            "$table->foreignId('hero_id')->constrained('hero_profiles');",
            "$table->string('adventure_type');",
            "$table->json('rewards')->nullable();",
            "$table->dateTime('started_at');",
            "$table->dateTime('completed_at')->nullable();",
        ],
    },
    {
        "slug": "create_village_effects_table",
        "table": "village_effects",
        "fields": [
            "$table->foreignId('village_id')->constrained('vdata_villages');",
            "$table->string('effect_type');",
            "$table->json('modifiers')->nullable();",
            "$table->dateTime('activated_at');",
            "$table->dateTime('expires_at')->nullable();",
        ],
    },
    {
        "slug": "create_village_bonuses_table",
        "table": "village_bonuses",
        "fields": [
            "$table->foreignId('village_id')->constrained('vdata_villages');",
            "$table->string('bonus_type');",
            "$table->unsignedInteger('value')->default(0);",
            "$table->date('applied_on');",
            "$table->unique(['village_id', 'bonus_type', 'applied_on']);",
        ],
    },
    {
        "slug": "create_village_upkeep_table",
        "table": "village_upkeep",
        "fields": [
            "$table->foreignId('village_id')->constrained('vdata_villages');",
            "$table->unsignedInteger('crop_consumption')->default(0);",
            "$table->unsignedInteger('crop_production')->default(0);",
            "$table->date('calculated_on');",
            "$table->unique(['village_id', 'calculated_on']);",
        ],
    },
    {
        "slug": "create_village_logistics_table",
        "table": "village_logistics",
        "fields": [
            "$table->foreignId('village_id')->constrained('vdata_villages');",
            "$table->unsignedInteger('warehouse_capacity')->default(0);",
            "$table->unsignedInteger('granary_capacity')->default(0);",
            "$table->json('trade_routes')->nullable();",
        ],
    },

    # Movement tables
    {
        "slug": "create_movement_paths_table",
        "table": "movement_paths",
        "fields": [
            "$table->foreignId('world_id')->constrained('wdata_worlds');",
            "$table->foreignId('origin_village_id')->constrained('vdata_villages');",
            "$table->foreignId('target_village_id')->constrained('vdata_villages');",
            "$table->unsignedInteger('duration_seconds');",
            "$table->string('movement_type');",
        ],
    },
    {
        "slug": "create_movement_waypoints_table",
        "table": "movement_waypoints",
        "fields": [
            "$table->foreignId('path_id')->constrained('movement_paths');",
            "$table->unsignedInteger('sequence');",
            "$table->foreignId('tile_id')->constrained('wdata_tiles');",
            "$table->unsignedInteger('arrival_offset')->default(0);",
            "$table->unique(['path_id', 'sequence']);",
        ],
    },
    {
        "slug": "create_movement_delays_table",
        "table": "movement_delays",
        "fields": [
            "$table->foreignId('path_id')->constrained('movement_paths');",
            "$table->string('reason');",
            "$table->unsignedInteger('delay_seconds')->default(0);",
            "$table->dateTime('applied_at');",
        ],
    },
    {
        "slug": "create_a2b_attacks_table",
        "table": "a2b_attacks",
        "fields": [
            "$table->foreignId('path_id')->constrained('movement_paths');",
            "$table->foreignId('attacker_village_id')->constrained('vdata_villages');",
            "$table->foreignId('defender_village_id')->constrained('vdata_villages');",
            "$table->json('payload')->nullable();",
            "$table->dateTime('scheduled_at');",
        ],
    },
    {
        "slug": "create_a2b_raids_table",
        "table": "a2b_raids",
        "fields": [
            "$table->foreignId('path_id')->constrained('movement_paths');",
            "$table->foreignId('attacker_village_id')->constrained('vdata_villages');",
            "$table->foreignId('defender_village_id')->constrained('vdata_villages');",
            "$table->string('priority')->default('normal');",
            "$table->dateTime('departing_at');",
        ],
    },
    {
        "slug": "create_a2b_spy_reports_table",
        "table": "a2b_spy_reports",
        "fields": [
            "$table->foreignId('path_id')->constrained('movement_paths');",
            "$table->foreignId('attacker_village_id')->constrained('vdata_villages');",
            "$table->foreignId('defender_village_id')->constrained('vdata_villages');",
            "$table->json('intel')->nullable();",
            "$table->dateTime('reported_at');",
        ],
    },
    {
        "slug": "create_enforcement_requests_table",
        "table": "enforcement_requests",
        "fields": [
            "$table->foreignId('requester_village_id')->constrained('vdata_villages');",
            "$table->foreignId('target_village_id')->constrained('vdata_villages');",
            "$table->json('requested_units')->nullable();",
            "$table->dateTime('needed_by');",
        ],
    },
    {
        "slug": "create_enforcement_responses_table",
        "table": "enforcement_responses",
        "fields": [
            "$table->foreignId('request_id')->constrained('enforcement_requests');",
            "$table->foreignId('responder_village_id')->constrained('vdata_villages');",
            "$table->json('committed_units')->nullable();",
            "$table->dateTime('departing_at');",
        ],
    },
    {
        "slug": "create_enforcement_garrisons_table",
        "table": "enforcement_garrisons",
        "fields": [
            "$table->foreignId('village_id')->constrained('vdata_villages');",
            "$table->foreignId('source_village_id')->constrained('vdata_villages');",
            "$table->string('unit_type');",
            "$table->unsignedInteger('amount')->default(0);",
            "$table->dateTime('arrived_at');",
        ],
    },
    {
        "slug": "create_enforcement_history_table",
        "table": "enforcement_history",
        "fields": [
            "$table->foreignId('request_id')->constrained('enforcement_requests');",
            "$table->string('status');",
            "$table->json('details')->nullable();",
            "$table->dateTime('changed_at');",
        ],
    },

    # Alliance tables
    {
        "slug": "create_alidata_profiles_table",
        "table": "alidata_profiles",
        "fields": [
            "$table->foreignId('alliance_id')->constrained('alliances');",
            "$table->string('motto')->nullable();",
            "$table->text('description')->nullable();",
            "$table->string('banner_url')->nullable();",
        ],
    },
    {
        "slug": "create_alidata_statistics_table",
        "table": "alidata_statistics",
        "fields": [
            "$table->foreignId('alliance_id')->constrained('alliances');",
            "$table->string('metric');",
            "$table->bigInteger('value')->default(0);",
            "$table->date('recorded_on');",
            "$table->unique(['alliance_id', 'metric', 'recorded_on']);",
        ],
    },
    {
        "slug": "create_alidata_diplomacy_table",
        "table": "alidata_diplomacy",
        "fields": [
            "$table->foreignId('alliance_id')->constrained('alliances');",
            "$table->foreignId('related_alliance_id')->constrained('alliances');",
            "$table->string('relationship_type');",
            "$table->date('established_on');",
            "$table->unique(['alliance_id', 'related_alliance_id']);",
        ],
    },
    {
        "slug": "create_alidata_roles_table",
        "table": "alidata_roles",
        "fields": [
            "$table->foreignId('alliance_id')->constrained('alliances');",
            "$table->string('role_name');",
            "$table->string('role_key');",
            "$table->boolean('is_default')->default(false);",
            "$table->unique(['alliance_id', 'role_key']);",
        ],
    },
    {
        "slug": "create_alidata_role_permissions_table",
        "table": "alidata_role_permissions",
        "fields": [
            "$table->foreignId('role_id')->constrained('alidata_roles');",
            "$table->string('permission_key');",
            "$table->boolean('is_allowed')->default(true);",
            "$table->unique(['role_id', 'permission_key']);",
        ],
    },
    {
        "slug": "create_forum_categories_table",
        "table": "forum_categories",
        "fields": [
            "$table->string('name');",
            "$table->unsignedInteger('position')->default(0);",
            "$table->boolean('is_hidden')->default(false);",
        ],
    },
    {
        "slug": "create_forum_boards_table",
        "table": "forum_boards",
        "fields": [
            "$table->foreignId('category_id')->constrained('forum_categories');",
            "$table->string('title');",
            "$table->text('description')->nullable();",
            "$table->unsignedInteger('position')->default(0);",
        ],
    },
    {
        "slug": "create_forum_threads_table",
        "table": "forum_threads",
        "fields": [
            "$table->foreignId('board_id')->constrained('forum_boards');",
            "$table->foreignId('author_id')->constrained('users');",
            "$table->string('title');",
            "$table->boolean('is_locked')->default(false);",
        ],
    },
    {
        "slug": "create_forum_posts_table",
        "table": "forum_posts",
        "fields": [
            "$table->foreignId('thread_id')->constrained('forum_threads');",
            "$table->foreignId('author_id')->constrained('users');",
            "$table->text('content');",
            "$table->dateTime('posted_at');",
        ],
    },
    {
        "slug": "create_forum_post_revisions_table",
        "table": "forum_post_revisions",
        "fields": [
            "$table->foreignId('post_id')->constrained('forum_posts');",
            "$table->foreignId('editor_id')->nullable()->constrained('users');",
            "$table->text('content');",
            "$table->dateTime('edited_at');",
        ],
    },
    {
        "slug": "create_forum_moderation_logs_table",
        "table": "forum_moderation_logs",
        "fields": [
            "$table->foreignId('moderator_id')->constrained('users');",
            "$table->string('action');",
            "$table->json('context')->nullable();",
            "$table->dateTime('recorded_at');",
        ],
    },
    {
        "slug": "create_forum_permissions_table",
        "table": "forum_permissions",
        "fields": [
            "$table->foreignId('board_id')->constrained('forum_boards');",
            "$table->string('permission_key');",
            "$table->string('target_type');",
            "$table->unsignedBigInteger('target_id')->nullable();",
        ],
    },

    # Communication tables
    {
        "slug": "create_mdata_messages_table",
        "table": "mdata_messages",
        "fields": [
            "$table->foreignId('author_id')->constrained('users');",
            "$table->string('subject');",
            "$table->text('body');",
            "$table->boolean('is_system')->default(false);",
        ],
    },
    {
        "slug": "create_mdata_message_recipients_table",
        "table": "mdata_message_recipients",
        "fields": [
            "$table->foreignId('message_id')->constrained('mdata_messages');",
            "$table->foreignId('recipient_id')->constrained('users');",
            "$table->boolean('is_read')->default(false);",
            "$table->dateTime('read_at')->nullable();",
            "$table->unique(['message_id', 'recipient_id']);",
        ],
    },
    {
        "slug": "create_mdata_message_threads_table",
        "table": "mdata_message_threads",
        "fields": [
            "$table->string('thread_key')->unique();",
            "$table->string('topic');",
            "$table->foreignId('starter_id')->constrained('users');",
            "$table->dateTime('last_message_at')->nullable();",
        ],
    },
    {
        "slug": "create_mdata_message_archives_table",
        "table": "mdata_message_archives",
        "fields": [
            "$table->foreignId('message_id')->constrained('mdata_messages');",
            "$table->foreignId('archived_by')->constrained('users');",
            "$table->dateTime('archived_at');",
            "$table->string('reason')->nullable();",
        ],
    },
    {
        "slug": "create_ndata_notifications_table",
        "table": "ndata_notifications",
        "fields": [
            "$table->foreignId('user_id')->constrained('users');",
            "$table->string('notification_type');",
            "$table->json('payload')->nullable();",
            "$table->boolean('is_read')->default(false);",
        ],
    },
    {
        "slug": "create_ndata_notification_channels_table",
        "table": "ndata_notification_channels",
        "fields": [
            "$table->string('channel_key')->unique();",
            "$table->string('name');",
            "$table->json('configuration')->nullable();",
            "$table->boolean('is_active')->default(true);",
        ],
    },
    {
        "slug": "create_ndata_subscriptions_table",
        "table": "ndata_subscriptions",
        "fields": [
            "$table->foreignId('user_id')->constrained('users');",
            "$table->foreignId('channel_id')->constrained('ndata_notification_channels');",
            "$table->json('preferences')->nullable();",
            "$table->dateTime('subscribed_at');",
            "$table->unique(['user_id', 'channel_id']);",
        ],
    },
    {
        "slug": "create_ndata_delivery_logs_table",
        "table": "ndata_delivery_logs",
        "fields": [
            "$table->foreignId('notification_id')->constrained('ndata_notifications');",
            "$table->string('status');",
            "$table->json('metadata')->nullable();",
            "$table->dateTime('delivered_at')->nullable();",
        ],
    },

    # Supporting tables
    {
        "slug": "create_artifacts_table",
        "table": "artifacts",
        "fields": [
            "$table->string('artifact_code')->unique();",
            "$table->string('name');",
            "$table->string('rarity');",
            "$table->json('effects')->nullable();",
        ],
    },
    {
        "slug": "create_artifact_effects_table",
        "table": "artifact_effects",
        "fields": [
            "$table->foreignId('artifact_id')->constrained('artifacts');",
            "$table->string('effect_key');",
            "$table->json('modifiers')->nullable();",
            "$table->boolean('is_active')->default(true);",
            "$table->unique(['artifact_id', 'effect_key']);",
        ],
    },
    {
        "slug": "create_artifact_unlocks_table",
        "table": "artifact_unlocks",
        "fields": [
            "$table->foreignId('artifact_id')->constrained('artifacts');",
            "$table->foreignId('world_id')->constrained('wdata_worlds');",
            "$table->dateTime('unlocked_at');",
            "$table->foreignId('unlocked_by')->nullable()->constrained('users');",
        ],
    },
    {
        "slug": "create_artifact_ownerships_table",
        "table": "artifact_ownerships",
        "fields": [
            "$table->foreignId('artifact_id')->constrained('artifacts');",
            "$table->foreignId('owner_id')->nullable()->constrained('users');",
            "$table->foreignId('village_id')->nullable()->constrained('vdata_villages');",
            "$table->dateTime('acquired_at');",
        ],
    },
    {
        "slug": "create_market_listings_table",
        "table": "market_listings",
        "fields": [
            "$table->foreignId('village_id')->constrained('vdata_villages');",
            "$table->string('offer_type');",
            "$table->unsignedInteger('offer_amount');",
            "$table->unsignedInteger('request_amount');",
            "$table->string('status')->default('open');",
        ],
    },
    {
        "slug": "create_market_transactions_table",
        "table": "market_transactions",
        "fields": [
            "$table->foreignId('listing_id')->constrained('market_listings');",
            "$table->foreignId('buyer_village_id')->constrained('vdata_villages');",
            "$table->dateTime('executed_at');",
            "$table->unsignedInteger('trade_ratio')->default(100);",
        ],
    },
    {
        "slug": "create_market_trade_routes_table",
        "table": "market_trade_routes",
        "fields": [
            "$table->foreignId('owner_village_id')->constrained('vdata_villages');",
            "$table->foreignId('target_village_id')->constrained('vdata_villages');",
            "$table->unsignedInteger('wood')->default(0);",
            "$table->unsignedInteger('clay')->default(0);",
            "$table->unsignedInteger('iron')->default(0);",
            "$table->unsignedInteger('crop')->default(0);",
            "$table->time('departure_time');",
        ],
    },
    {
        "slug": "create_market_daily_prices_table",
        "table": "market_daily_prices",
        "fields": [
            "$table->foreignId('world_id')->constrained('wdata_worlds');",
            "$table->date('recorded_on');",
            "$table->unsignedInteger('wood_price');",
            "$table->unsignedInteger('clay_price');",
            "$table->unsignedInteger('iron_price');",
            "$table->unsignedInteger('crop_price');",
            "$table->unique(['world_id', 'recorded_on']);",
        ],
    },
    {
        "slug": "create_quests_table",
        "table": "quests",
        "fields": [
            "$table->string('quest_code')->unique();",
            "$table->string('title');",
            "$table->text('description');",
            "$table->boolean('is_repeatable')->default(false);",
        ],
    },
    {
        "slug": "create_quest_tasks_table",
        "table": "quest_tasks",
        "fields": [
            "$table->foreignId('quest_id')->constrained('quests');",
            "$table->string('task_code');",
            "$table->text('requirements');",
            "$table->unsignedInteger('task_order')->default(0);",
            "$table->unique(['quest_id', 'task_code']);",
        ],
    },
    {
        "slug": "create_quest_rewards_table",
        "table": "quest_rewards",
        "fields": [
            "$table->foreignId('quest_id')->constrained('quests');",
            "$table->string('reward_type');",
            "$table->unsignedInteger('quantity');",
            "$table->json('details')->nullable();",
        ],
    },
    {
        "slug": "create_quest_progress_table",
        "table": "quest_progress",
        "fields": [
            "$table->foreignId('quest_id')->constrained('quests');",
            "$table->foreignId('user_id')->constrained('users');",
            "$table->unsignedInteger('current_step')->default(0);",
            "$table->boolean('is_completed')->default(false);",
            "$table->dateTime('completed_at')->nullable();",
            "$table->unique(['quest_id', 'user_id']);",
        ],
    },
    {
        "slug": "create_support_tasks_table",
        "table": "support_tasks",
        "fields": [
            "$table->string('task_key')->unique();",
            "$table->string('category');",
            "$table->json('payload')->nullable();",
            "$table->string('status')->default('pending');",
        ],
    },
    {
        "slug": "create_support_metrics_table",
        "table": "support_metrics",
        "fields": [
            "$table->string('metric_key')->unique();",
            "$table->bigInteger('value')->default(0);",
            "$table->date('recorded_on');",
        ],
    },
    {
        "slug": "create_support_audits_table",
        "table": "support_audits",
        "fields": [
            "$table->string('action');",
            "$table->foreignId('performed_by')->nullable()->constrained('users');",
            "$table->json('details')->nullable();",
            "$table->dateTime('performed_at');",
        ],
    },
]

print(f"Prepared {len(entries)} migration definitions.")

os.makedirs(BASE_PATH, exist_ok=True)

sequence = 0
for entry in entries:
    timestamp = f"{BASE_TIMESTAMP}_{sequence:06d}"
    filename = f"{timestamp}_{entry['slug']}.php"
    path = os.path.join(BASE_PATH, filename)

    lines = ["$table->id();"] + entry["fields"] + ["$table->timestamps();"]
    body = "\n".join(f"{line}" for line in lines)
    body = indent(body, ' ' * 12)

    content = f"""<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{{
    public function up(): void
    {{
        Schema::create('{entry['table']}', function (Blueprint $table) {{
{body}
        }});
    }}

    public function down(): void
    {{
        Schema::dropIfExists('{entry['table']}');
    }}
}};
"""

    with open(path, 'w') as f:
        f.write(content)

    sequence += SEQUENCE_STEP

print(f"Generated {len(entries)} migration files in {BASE_PATH}.")
