<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('config');

        Schema::create('game_configurations', function (Blueprint $table): void {
            $table->id();
            $table->uuid('world_uuid')->nullable()->unique();
            $table->unsignedSmallInteger('map_size')->nullable();
            $table->string('patch_version', 32)->nullable();
            $table->timestamp('world_started_at')->nullable()->index();
            $table->boolean('is_installed')->default(false);
            $table->boolean('automation_enabled')->default(true);
            $table->timestamp('installation_started_at')->nullable();
            $table->timestamp('installation_completed_at')->nullable();
            $table->timestamp('last_backup_at')->nullable();
            $table->timestamp('last_system_cleanup_at')->nullable()->index();
            $table->timestamp('last_fake_auction_at')->nullable();
            $table->timestamp('last_natars_expand_at')->nullable();
            $table->timestamp('last_daily_gold_at')->nullable();
            $table->timestamp('last_daily_quest_reset_at')->nullable();
            $table->timestamp('last_medals_given_at')->nullable();
            $table->timestamp('last_alliance_contribution_reset_at')->nullable();
            $table->timestamp('start_emails_sent_at')->nullable();
            $table->timestamp('configuration_completed_at')->nullable();
            $table->timestamp('wonder_alert_sent_at')->nullable();
            $table->timestamp('artifacts_released_at')->nullable();
            $table->timestamp('wonder_plans_released_at')->nullable();
            $table->timestamp('world_finished_at')->nullable();
            $table->timestamp('finish_status_set_at')->nullable();
            $table->timestamp('post_service_completed_at')->nullable();
            $table->timestamp('truce_starts_at')->nullable();
            $table->timestamp('truce_ends_at')->nullable();
            $table->unsignedTinyInteger('truce_reason_id')->nullable();
            $table->boolean('fake_account_process_enabled')->default(true);
            $table->boolean('maintenance_mode')->default(false)->index();
            $table->unsignedInteger('maintenance_delay_seconds')->default(0);
            $table->boolean('needs_restart')->default(false);
            $table->boolean('is_restore')->default(false);
            $table->string('login_info_title')->nullable();
            $table->text('login_info_html')->nullable();
            $table->text('maintenance_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_configurations');
    }
};
