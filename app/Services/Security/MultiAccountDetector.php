<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Enums\MultiAccountAlertSeverity;
use App\Enums\MultiAccountAlertStatus;
use App\Models\LoginActivity;
use App\Models\MultiAccountAlert;
use App\Models\User;
use App\Notifications\MultiAccountAlertRaised;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Notifications\Notification as IlluminateNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class MultiAccountDetector
{
    public function __construct(
        protected MultiAccountRules $rules,
    ) {}

    public function record(LoginActivity $activity): void
    {
        $timestamp = $activity->logged_at ?? $activity->created_at ?? now();
        $windowMinutes = (int) config('multiaccount.window.minutes', 1440);
        $windowStart = $timestamp->copy()->subMinutes($windowMinutes);

        $vpnSuspected = $this->rules->isLikelyVpn($activity->ip_address, $activity->user_agent);

        $this->evaluateSource(
            sourceType: 'ip',
            identifier: $activity->ip_address,
            activity: $activity,
            windowStart: $windowStart,
            vpnSuspected: $vpnSuspected,
            suppressionReason: $this->rules->allowlistReason($activity->ip_address, null),
        );

        if (! blank($activity->device_hash)) {
            $this->evaluateSource(
                sourceType: 'device',
                identifier: $activity->device_hash,
                activity: $activity,
                windowStart: $windowStart,
                vpnSuspected: $vpnSuspected,
                suppressionReason: $this->rules->allowlistReason(null, $activity->device_hash),
            );
        }
    }

    protected function evaluateSource(
        string $sourceType,
        ?string $identifier,
        LoginActivity $activity,
        Carbon $windowStart,
        bool $vpnSuspected,
        ?string $suppressionReason,
    ): void {
        if (blank($identifier)) {
            return;
        }

        $activities = LoginActivity::query()
            ->when($sourceType === 'ip', fn ($query) => $query->where('ip_address', $identifier))
            ->when($sourceType === 'device', fn ($query) => $query->where('device_hash', $identifier))
            ->whereBetween('logged_at', [$windowStart, $activity->logged_at ?? $activity->created_at ?? now()])
            ->orderBy('logged_at')
            ->get();

        if ($activities->isEmpty()) {
            return;
        }

        $userIds = $activities->pluck('user_id')->unique()->sort()->values();
        if ($userIds->count() < 2 && $suppressionReason === null) {
            return;
        }

        $occurrences = $activities->count();
        $severity = $this->determineSeverity($userIds->count(), $occurrences, $vpnSuspected);

        if ($severity === null && $suppressionReason === null) {
            return;
        }

        $groupKey = $this->buildGroupKey($sourceType, $identifier, $userIds);

        $alert = MultiAccountAlert::query()->firstOrNew(['group_key' => $groupKey]);

        if (! $alert->exists) {
            $alert->alert_id = (string) Str::uuid();
            $alert->first_seen_at = $activities->first()->logged_at ?? $activities->first()->created_at;
        }

        $status = $suppressionReason !== null
            ? MultiAccountAlertStatus::Suppressed
            : MultiAccountAlertStatus::Open;

        $alert->fill([
            'source_type' => $sourceType,
            'ip_address' => $activity->ip_address,
            'device_hash' => $sourceType === 'device' ? $identifier : $activity->device_hash,
            'user_ids' => $userIds->all(),
            'occurrences' => $occurrences,
            'window_started_at' => $activities->first()->logged_at ?? $activities->first()->created_at,
            'last_seen_at' => $activities->last()->logged_at ?? $activities->last()->created_at,
            'severity' => $severity ?? MultiAccountAlertSeverity::Low,
            'status' => $status,
            'suppression_reason' => $suppressionReason,
            'metadata' => $this->buildMetadata($sourceType, $identifier, $activities, $vpnSuspected),
        ])->save();

        if ($status === MultiAccountAlertStatus::Open) {
            $this->maybeNotify($alert);
        }
    }

    protected function determineSeverity(int $uniqueUsers, int $occurrences, bool $vpnSuspected): ?MultiAccountAlertSeverity
    {
        $thresholds = collect(config('multiaccount.thresholds', []));
        if ($thresholds->isEmpty()) {
            return $uniqueUsers >= 2 ? MultiAccountAlertSeverity::Low : null;
        }

        $order = [
            MultiAccountAlertSeverity::Critical,
            MultiAccountAlertSeverity::High,
            MultiAccountAlertSeverity::Medium,
            MultiAccountAlertSeverity::Low,
        ];

        $detected = null;

        foreach ($order as $level) {
            $config = $thresholds->get($level->value);
            if (! is_array($config)) {
                continue;
            }

            $accountThreshold = (int) ($config['unique_accounts'] ?? 0);
            $occurrenceThreshold = (int) ($config['occurrences'] ?? 0);

            if ($uniqueUsers >= $accountThreshold && $occurrences >= $occurrenceThreshold) {
                $detected = $level;
                break;
            }
        }

        if ($detected === null) {
            return null;
        }

        if (! $vpnSuspected) {
            return $detected;
        }

        return match ($detected) {
            MultiAccountAlertSeverity::Critical => MultiAccountAlertSeverity::High,
            MultiAccountAlertSeverity::High => MultiAccountAlertSeverity::Medium,
            MultiAccountAlertSeverity::Medium => MultiAccountAlertSeverity::Low,
            default => MultiAccountAlertSeverity::Low,
        };
    }

    protected function buildGroupKey(string $sourceType, string $identifier, Collection $userIds): string
    {
        return sha1($sourceType.'|'.$identifier.'|'.implode('-', $userIds->all()));
    }

    /**
     * @param  EloquentCollection<int, LoginActivity>  $activities
     * @return array<string, mixed>
     */
    protected function buildMetadata(string $sourceType, string $identifier, EloquentCollection $activities, bool $vpnSuspected): array
    {
        $timeline = $activities
            ->sortByDesc(static fn (LoginActivity $activity): ?Carbon => $activity->logged_at ?? $activity->created_at)
            ->map(static function (LoginActivity $activity): array {
                return [
                    'login_activity_id' => $activity->getKey(),
                    'user_id' => $activity->user_id,
                    'acting_sitter_id' => $activity->acting_sitter_id,
                    'via_sitter' => $activity->via_sitter,
                    'ip_address' => $activity->ip_address,
                    'logged_at' => optional($activity->logged_at ?? $activity->created_at)->toAtomString(),
                ];
            })
            ->take(25)
            ->values()
            ->all();

        $countsByUser = $activities
            ->groupBy('user_id')
            ->map(static fn (Collection $items): int => $items->count())
            ->all();

        return [
            'source' => [
                'type' => $sourceType,
                'identifier' => $identifier,
            ],
            'vpn_suspected' => $vpnSuspected,
            'user_counts' => $countsByUser,
            'recent_timeline' => $timeline,
        ];
    }

    protected function maybeNotify(MultiAccountAlert $alert): void
    {
        $severity = $alert->severity;
        if (! $severity instanceof MultiAccountAlertSeverity) {
            return;
        }

        if (! in_array($severity, [MultiAccountAlertSeverity::High, MultiAccountAlertSeverity::Critical], true)) {
            return;
        }

        $cooldown = (int) config('multiaccount.notifications.cooldown_minutes', 60);
        if ($alert->last_notified_at !== null && $alert->last_notified_at->diffInMinutes(now()) < $cooldown) {
            return;
        }

        $notification = $this->buildNotification($alert);
        if ($notification === null) {
            return;
        }

        $recipients = $this->resolveRecipients();
        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, $notification);
        }

        $this->sendWebhook($alert);

        $alert->forceFill([
            'last_notified_at' => now(),
        ])->save();
    }

    protected function buildNotification(MultiAccountAlert $alert): ?IlluminateNotification
    {
        return new MultiAccountAlertRaised($alert);
    }

    /**
     * @return EloquentCollection<int, User>
     */
    protected function resolveRecipients(): EloquentCollection
    {
        return User::query()
            ->whereIn('legacy_uid', [0, 2])
            ->get();
    }

    protected function sendWebhook(MultiAccountAlert $alert): void
    {
        $webhookUrl = config('multiaccount.notifications.webhook_url');
        if ($webhookUrl === null || $webhookUrl === '') {
            return;
        }

        try {
            Http::timeout(5)->post($webhookUrl, [
                'alert_id' => $alert->alert_id,
                'severity' => $alert->severity?->value,
                'status' => $alert->status?->value,
                'source_type' => $alert->source_type,
                'device_hash' => $alert->device_hash,
                'ip_address' => $alert->ip_address,
                'user_ids' => $alert->user_ids,
                'occurrences' => $alert->occurrences,
                'last_seen_at' => optional($alert->last_seen_at)->toAtomString(),
                'metadata' => $alert->metadata,
            ]);
        } catch (\Throwable) {
            // Silently swallow webhook failures to avoid blocking the login flow.
        }
    }

    public function dismissAlert(MultiAccountAlert $alert, User $actor, ?string $notes = null): void
    {
        $alert->forceFill([
            'status' => MultiAccountAlertStatus::Dismissed,
            'dismissed_at' => now(),
            'dismissed_by_user_id' => $actor->getKey(),
            'notes' => $notes,
        ])->save();
    }

    public function resolveAlert(MultiAccountAlert $alert, User $actor, ?string $notes = null): void
    {
        $alert->forceFill([
            'status' => MultiAccountAlertStatus::Resolved,
            'resolved_at' => now(),
            'resolved_by_user_id' => $actor->getKey(),
            'notes' => $notes,
        ])->save();
    }
}
