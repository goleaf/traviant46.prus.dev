<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Enums\MultiAccountAlertSeverity;
use App\Enums\MultiAccountAlertStatus;
use App\Models\MultiAccountAlert;
use App\Models\User;
use App\Monitoring\Metrics\MetricRecorder;
use App\Notifications\MultiAccountAlertRaised;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Notifications\Notification as IlluminateNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Throwable;

class MultiAccountAlertsService
{
    private bool $suppressNotifications = false;

    public function __construct(
        private readonly MetricRecorder $metrics,
    ) {}

    /**
     * @param array<int, int> $userIds
     * @param array<string, mixed> $metadata
     */
    public function upsert(
        string $groupKey,
        string $sourceType,
        ?string $worldId,
        ?string $ipAddress,
        ?string $ipAddressHash,
        ?string $deviceHash,
        array $userIds,
        int $occurrences,
        ?Carbon $windowStartedAt,
        ?Carbon $firstSeenAt,
        ?Carbon $lastSeenAt,
        ?MultiAccountAlertSeverity $severity,
        MultiAccountAlertStatus $status,
        ?string $suppressionReason,
        array $metadata,
        bool $shouldNotify = true,
    ): MultiAccountAlert {
        $alert = MultiAccountAlert::query()->firstOrNew(['group_key' => $groupKey]);
        $wasExisting = $alert->exists;

        if (! $wasExisting) {
            $alert->alert_id = (string) Str::uuid();
            $alert->first_seen_at = $firstSeenAt ?? $windowStartedAt ?? $lastSeenAt ?? now();
        }

        $resolvedSeverity = $severity ?? MultiAccountAlertSeverity::Low;

        $alert->fill([
            'source_type' => $sourceType,
            'world_id' => $worldId,
            'ip_address' => $ipAddress,
            'ip_address_hash' => $ipAddressHash,
            'device_hash' => $deviceHash,
            'user_ids' => array_values($userIds),
            'occurrences' => $occurrences,
            'window_started_at' => $windowStartedAt,
            'last_seen_at' => $lastSeenAt,
            'severity' => $resolvedSeverity,
            'status' => $status,
            'suppression_reason' => $suppressionReason,
            'metadata' => $metadata,
        ])->save();

        $this->metrics->increment('security.multi_account_alert', 1.0, [
            'state' => $wasExisting ? 'updated' : 'created',
            'source' => $sourceType,
            'severity' => $resolvedSeverity->value,
            'status' => $status->value,
            'suppressed' => $status === MultiAccountAlertStatus::Suppressed ? 'yes' : 'no',
            'world' => $worldId !== null && $worldId !== '' ? $worldId : 'global',
        ]);

        if ($shouldNotify && ! $this->suppressNotifications && $status === MultiAccountAlertStatus::Open) {
            $this->maybeNotify($alert);
        }

        return $alert;
    }

    public function resolve(MultiAccountAlert $alert, User $actor, ?string $notes = null): MultiAccountAlert
    {
        $alert->forceFill([
            'status' => MultiAccountAlertStatus::Resolved,
            'resolved_at' => now(),
            'resolved_by_user_id' => $actor->getKey(),
            'notes' => $notes,
        ])->save();

        return $alert;
    }

    public function dismiss(MultiAccountAlert $alert, User $actor, ?string $notes = null): MultiAccountAlert
    {
        $alert->forceFill([
            'status' => MultiAccountAlertStatus::Dismissed,
            'dismissed_at' => now(),
            'dismissed_by_user_id' => $actor->getKey(),
            'notes' => $notes,
        ])->save();

        return $alert;
    }

    public function withoutNotifications(callable $callback): void
    {
        $previous = $this->suppressNotifications;
        $this->suppressNotifications = true;

        try {
            $callback();
        } finally {
            $this->suppressNotifications = $previous;
        }
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

            $this->metrics->increment('security.multi_account_notification', 1.0, [
                'channel' => 'broadcast',
                'severity' => $severity->value,
            ]);
        }

        if ($this->sendWebhook($alert)) {
            $this->metrics->increment('security.multi_account_notification', 1.0, [
                'channel' => 'webhook',
                'severity' => $severity->value,
            ]);
        }

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

    protected function sendWebhook(MultiAccountAlert $alert): bool
    {
        $webhookUrl = config('multiaccount.notifications.webhook_url');
        if ($webhookUrl === null || $webhookUrl === '') {
            return false;
        }

        try {
            $response = Http::timeout(5)->post($webhookUrl, [
                'alert_id' => $alert->alert_id,
                'severity' => $alert->severity?->value,
                'status' => $alert->status?->value,
                'source_type' => $alert->source_type,
                'device_hash' => $alert->device_hash,
                'ip_address' => $alert->ip_address,
                'ip_address_hash' => $alert->ip_address_hash,
                'world_id' => $alert->world_id,
                'user_ids' => $alert->user_ids,
                'occurrences' => $alert->occurrences,
                'last_seen_at' => optional($alert->last_seen_at)->toAtomString(),
                'metadata' => $alert->metadata,
            ]);

            return $response->successful();
        } catch (Throwable) {
            return false;
        }
    }
}
