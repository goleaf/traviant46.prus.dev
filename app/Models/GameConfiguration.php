<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameConfiguration extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'game_configurations';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'world_uuid',
        'map_size',
        'patch_version',
        'world_started_at',
        'is_installed',
        'automation_enabled',
        'installation_started_at',
        'installation_completed_at',
        'last_backup_at',
        'last_system_cleanup_at',
        'last_fake_auction_at',
        'last_natars_expand_at',
        'last_daily_gold_at',
        'last_daily_quest_reset_at',
        'last_medals_given_at',
        'last_alliance_contribution_reset_at',
        'start_emails_sent_at',
        'configuration_completed_at',
        'wonder_alert_sent_at',
        'artifacts_released_at',
        'wonder_plans_released_at',
        'world_finished_at',
        'finish_status_set_at',
        'post_service_completed_at',
        'truce_starts_at',
        'truce_ends_at',
        'truce_reason_id',
        'fake_account_process_enabled',
        'maintenance_mode',
        'maintenance_delay_seconds',
        'needs_restart',
        'is_restore',
        'login_info_title',
        'login_info_html',
        'maintenance_message',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'world_started_at' => 'datetime',
        'is_installed' => 'boolean',
        'automation_enabled' => 'boolean',
        'installation_started_at' => 'datetime',
        'installation_completed_at' => 'datetime',
        'last_backup_at' => 'datetime',
        'last_system_cleanup_at' => 'datetime',
        'last_fake_auction_at' => 'datetime',
        'last_natars_expand_at' => 'datetime',
        'last_daily_gold_at' => 'datetime',
        'last_daily_quest_reset_at' => 'datetime',
        'last_medals_given_at' => 'datetime',
        'last_alliance_contribution_reset_at' => 'datetime',
        'start_emails_sent_at' => 'datetime',
        'configuration_completed_at' => 'datetime',
        'wonder_alert_sent_at' => 'datetime',
        'artifacts_released_at' => 'datetime',
        'wonder_plans_released_at' => 'datetime',
        'world_finished_at' => 'datetime',
        'finish_status_set_at' => 'datetime',
        'post_service_completed_at' => 'datetime',
        'truce_starts_at' => 'datetime',
        'truce_ends_at' => 'datetime',
        'fake_account_process_enabled' => 'boolean',
        'maintenance_mode' => 'boolean',
        'maintenance_delay_seconds' => 'integer',
        'needs_restart' => 'boolean',
        'is_restore' => 'boolean',
    ];
}
