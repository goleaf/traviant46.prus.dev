<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alliances', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('tag', 10)->unique();
            $table->text('public_description')->nullable();
            $table->text('internal_description')->nullable();
            $table->text('news')->nullable();
            $table->unsignedTinyInteger('member_limit')->default(0);
            $table->unsignedBigInteger('total_attack_points')->default(0);
            $table->unsignedBigInteger('total_defense_points')->default(0);
            $table->unsignedBigInteger('weekly_attack_points')->default(0);
            $table->unsignedBigInteger('weekly_defense_points')->default(0);
            $table->unsignedBigInteger('weekly_robber_points')->default(0);
            $table->unsignedBigInteger('weekly_population_change')->default(0);
            $table->timestamps();
        });

        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('alliance_id')->nullable()->constrained('alliances')->nullOnDelete();
            $table->unsignedInteger('legacy_id')->nullable()->unique();
            $table->string('name', 20)->unique();
            $table->enum('tribe', ['romans', 'teutons', 'gauls', 'egyptians', 'huns'])->default('romans');
            $table->enum('gender', ['male', 'female'])->default('male');
            $table->unsignedBigInteger('population')->default(0);
            $table->unsignedInteger('culture_points')->default(0);
            $table->unsignedInteger('villages_count')->default(0);
            $table->timestamp('beginners_protection_ends_at')->nullable();
            $table->timestamp('vacation_until')->nullable();
            $table->boolean('is_hidden')->default(false);
            $table->json('ui_preferences')->nullable();
            $table->timestamps();
        });

        Schema::create('alliance_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alliance_id')->constrained('alliances')->cascadeOnDelete();
            $table->foreignId('player_id')->constrained('players')->cascadeOnDelete();
            $table->string('role', 30)->default('member');
            $table->boolean('is_leader')->default(false);
            $table->boolean('is_founder')->default(false);
            $table->unsignedBigInteger('contribution_points')->default(0);
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();
            $table->unique(['alliance_id', 'player_id']);
        });

        Schema::table('alliances', function (Blueprint $table) {
            $table->foreignId('leader_id')->nullable()->constrained('players')->nullOnDelete();
        });

        Schema::create('building_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('category', 50)->nullable();
            $table->unsignedTinyInteger('max_level')->default(20);
            $table->boolean('is_resource_field')->default(false);
            $table->json('base_cost');
            $table->json('production')->nullable();
            $table->json('bonuses')->nullable();
            $table->timestamps();
        });

        Schema::create('troop_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->enum('tribe', ['romans', 'teutons', 'gauls', 'egyptians', 'huns']);
            $table->foreignId('training_building_type_id')->nullable()->constrained('building_types')->nullOnDelete();
            $table->unsignedSmallInteger('attack');
            $table->unsignedSmallInteger('defense_infantry');
            $table->unsignedSmallInteger('defense_cavalry');
            $table->unsignedTinyInteger('speed');
            $table->unsignedSmallInteger('carry_capacity');
            $table->unsignedTinyInteger('crop_consumption');
            $table->json('cost');
            $table->timestamps();
        });

        Schema::create('villages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained('players')->cascadeOnDelete();
            $table->string('name', 45);
            $table->integer('x_coordinate');
            $table->integer('y_coordinate');
            $table->unsignedTinyInteger('field_type')->default(0);
            $table->boolean('is_capital')->default(false);
            $table->boolean('is_world_wonder')->default(false);
            $table->boolean('is_oasis')->default(false);
            $table->unsignedInteger('population')->default(0);
            $table->unsignedInteger('culture_points')->default(0);
            $table->decimal('loyalty', 6, 2)->default(100.00);
            $table->timestamp('loyalty_updated_at')->nullable();
            $table->timestamp('founded_at')->nullable();
            $table->timestamps();
            $table->unique(['x_coordinate', 'y_coordinate']);
        });

        Schema::table('players', function (Blueprint $table) {
            $table->foreignId('capital_village_id')->nullable()->constrained('villages')->nullOnDelete();
        });

        Schema::create('village_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('village_id')->constrained('villages')->cascadeOnDelete();
            $table->unsignedTinyInteger('slot');
            $table->foreignId('building_type_id')->nullable()->constrained('building_types')->nullOnDelete();
            $table->unsignedTinyInteger('level')->default(0);
            $table->boolean('is_under_construction')->default(false);
            $table->timestamps();
            $table->unique(['village_id', 'slot']);
        });

        Schema::create('village_resource_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('village_id')->constrained('villages')->cascadeOnDelete();
            $table->double('wood', 15, 4)->default(0);
            $table->double('clay', 15, 4)->default(0);
            $table->double('iron', 15, 4)->default(0);
            $table->double('crop', 15, 4)->default(0);
            $table->unsignedBigInteger('wood_production')->default(0);
            $table->unsignedBigInteger('clay_production')->default(0);
            $table->unsignedBigInteger('iron_production')->default(0);
            $table->unsignedBigInteger('crop_production')->default(0);
            $table->unsignedBigInteger('warehouse_capacity')->default(800);
            $table->unsignedBigInteger('granary_capacity')->default(800);
            $table->unsignedInteger('crop_consumption')->default(0);
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();
            $table->unique('village_id');
        });

        Schema::create('building_upgrade_queue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('village_id')->constrained('villages')->cascadeOnDelete();
            $table->unsignedTinyInteger('slot');
            $table->foreignId('building_type_id')->nullable()->constrained('building_types')->nullOnDelete();
            $table->unsignedTinyInteger('target_level');
            $table->timestamp('queued_at');
            $table->timestamp('completes_at');
            $table->boolean('is_master_builder')->default(false);
            $table->timestamps();
        });

        Schema::create('village_troops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('village_id')->constrained('villages')->cascadeOnDelete();
            $table->foreignId('troop_type_id')->constrained('troop_types')->cascadeOnDelete();
            $table->unsignedInteger('stationed')->default(0);
            $table->unsignedInteger('training')->default(0);
            $table->unsignedInteger('away')->default(0);
            $table->timestamps();
            $table->unique(['village_id', 'troop_type_id']);
        });

        Schema::create('troop_training_queues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('village_id')->constrained('villages')->cascadeOnDelete();
            $table->foreignId('troop_type_id')->constrained('troop_types')->cascadeOnDelete();
            $table->unsignedTinyInteger('building_slot');
            $table->unsignedInteger('amount');
            $table->timestamp('training_started_at');
            $table->timestamp('training_ends_at');
            $table->timestamps();
        });

        Schema::create('troop_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('origin_village_id')->nullable()->constrained('villages')->nullOnDelete();
            $table->foreignId('target_village_id')->nullable()->constrained('villages')->nullOnDelete();
            $table->foreignId('player_id')->nullable()->constrained('players')->nullOnDelete();
            $table->enum('movement_type', ['reinforcement', 'attack', 'raid', 'settle', 'scout', 'return', 'support']);
            $table->json('units');
            $table->boolean('hero_included')->default(false);
            $table->timestamp('started_at');
            $table->timestamp('arrives_at');
            $table->timestamp('returns_at')->nullable();
            $table->timestamps();
        });

        Schema::create('hero_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained('players')->cascadeOnDelete();
            $table->foreignId('home_village_id')->nullable()->constrained('villages')->nullOnDelete();
            $table->foreignId('current_village_id')->nullable()->constrained('villages')->nullOnDelete();
            $table->unsignedInteger('experience')->default(0);
            $table->unsignedSmallInteger('level')->default(0);
            $table->decimal('health', 5, 2)->default(100);
            $table->boolean('is_dead')->default(false);
            $table->unsignedInteger('adventure_points')->default(0);
            $table->json('attributes')->nullable();
            $table->timestamps();
        });

        Schema::create('hero_adventures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained('players')->cascadeOnDelete();
            $table->foreignId('hero_state_id')->constrained('hero_states')->cascadeOnDelete();
            $table->foreignId('target_village_id')->nullable()->constrained('villages')->nullOnDelete();
            $table->integer('target_x');
            $table->integer('target_y');
            $table->enum('difficulty', ['easy', 'hard']);
            $table->enum('reward_type', ['resource', 'equipment', 'hero_experience', 'silver', 'culture_points', 'animal']);
            $table->json('reward_payload')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed', 'failed'])->default('pending');
            $table->timestamp('available_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_player_id')->nullable()->constrained('players')->nullOnDelete();
            $table->foreignId('recipient_player_id')->nullable()->constrained('players')->nullOnDelete();
            $table->foreignId('origin_village_id')->nullable()->constrained('villages')->nullOnDelete();
            $table->foreignId('target_village_id')->nullable()->constrained('villages')->nullOnDelete();
            $table->enum('report_type', ['attack', 'reinforcement', 'trade', 'scout', 'system', 'quest', 'other']);
            $table->string('subject');
            $table->text('body')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('sent_at');
            $table->timestamp('read_at')->nullable();
            $table->boolean('archived_by_recipient')->default(false);
            $table->timestamps();
        });

        Schema::create('quest_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 100)->unique();
            $table->string('category', 50);
            $table->string('title');
            $table->text('description');
            $table->json('requirements');
            $table->json('rewards');
            $table->boolean('repeatable')->default(false);
            $table->timestamps();
        });

        Schema::create('quest_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained('players')->cascadeOnDelete();
            $table->foreignId('quest_definition_id')->constrained('quest_definitions')->cascadeOnDelete();
            $table->json('progress_payload')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['player_id', 'quest_definition_id']);
        });

        Schema::create('daily_quest_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained('players')->cascadeOnDelete();
            $table->date('quest_date');
            $table->unsignedTinyInteger('completed_tasks')->default(0);
            $table->json('tasks_payload')->nullable();
            $table->timestamp('reset_at')->nullable();
            $table->timestamps();
            $table->unique(['player_id', 'quest_date']);
        });

        Schema::create('farm_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained('players')->cascadeOnDelete();
            $table->foreignId('source_village_id')->nullable()->constrained('villages')->nullOnDelete();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('farm_list_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_list_id')->constrained('farm_lists')->cascadeOnDelete();
            $table->foreignId('target_village_id')->nullable()->constrained('villages')->nullOnDelete();
            $table->json('troops')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farm_list_entries');
        Schema::dropIfExists('farm_lists');
        Schema::dropIfExists('daily_quest_progress');
        Schema::dropIfExists('quest_progress');
        Schema::dropIfExists('quest_definitions');
        Schema::dropIfExists('reports');
        Schema::dropIfExists('hero_adventures');
        Schema::dropIfExists('hero_states');
        Schema::dropIfExists('troop_movements');
        Schema::dropIfExists('troop_training_queues');
        Schema::dropIfExists('village_troops');
        Schema::dropIfExists('building_upgrade_queue');
        Schema::dropIfExists('village_resource_states');
        Schema::dropIfExists('village_fields');
        Schema::table('players', function (Blueprint $table) {
            if (Schema::hasColumn('players', 'capital_village_id')) {
                $table->dropConstrainedForeignId('capital_village_id');
            }
        });
        Schema::dropIfExists('villages');
        Schema::dropIfExists('troop_types');
        Schema::dropIfExists('building_types');
        Schema::table('alliances', function (Blueprint $table) {
            if (Schema::hasColumn('alliances', 'leader_id')) {
                $table->dropConstrainedForeignId('leader_id');
            }
        });
        Schema::dropIfExists('alliance_members');
        Schema::dropIfExists('players');
        Schema::dropIfExists('alliances');
    }
};
