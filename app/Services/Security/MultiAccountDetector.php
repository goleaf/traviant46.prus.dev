<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Enums\MultiAccountAlertSeverity;
use App\Enums\MultiAccountAlertStatus;
use App\Models\LoginActivity;
use App\Models\MultiAccountAlert;
use App\Models\User;
use App\Monitoring\Metrics\MetricRecorder;
use App\Notifications\MultiAccountAlertRaised;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Notifications\Notification as IlluminateNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Throwable;

class MultiAccountDetector
{
    protected bool $suppressNotifications = false;

    public function __construct(
        protected MultiAccountRules $rules,
        protected MetricRecorder $metrics,
    ) {}

    public function record(LoginActivity $activity): void
    {
        $timestamp = $activity->logged_at ?? $activity->created_at ?? now();
        $windowMinutes = (int) config('multiaccount.window.minutes', 1440);
        $windowStart = $timestamp->copy()->subMinutes($windowMinutes);

        $vpnSuspected = $this->rules->isLikelyVpn($activity->ip_address, $activity->user_agent);

        $ipMatches = collect([
            $activity->ip_address,
            $activity->ip_address_hash,
        ])->filter(static fn ($value) => $value !== null && $value !== '')->values();

        $this->evaluateSource(
            sourceType: 'ip',
            identifier: $ipMatches->first() ?? $activity->ip_address,
            activity: $activity,
            windowStart: $windowStart,
            vpnSuspected: $vpnSuspected,
            suppressionReason: $this->rules->allowlistReason($activity->ip_address, null),
            matchIdentifiers: $ipMatches->all(),
        );

        if (! blank($activity->device_hash)) {
            $this->evaluateSource(
                sourceType: 'device',
                identifier: $activity->device_hash,
                activity: $activity,
                windowStart: $windowStart,
                vpnSuspected: $vpnSuspected,
                suppressionReason: $this->rules->allowlistReason(null, $activity->device_hash),
                matchIdentifiers: [$activity->device_hash],
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
        array $matchIdentifiers = [],
    ): void {
        if (blank($identifier)) {
            return;
        }

        $normalizedMatches = collect($matchIdentifiers)
            ->merge([$identifier])
            ->filter(static fn ($value) => $value !== null && $value !== '')
            ->unique()
            ->values();

        $activities = LoginActivity::query()
            ->when($sourceType === 'ip', function (Builder $query) use ($normalizedMatches): Builder {
                if ($normalizedMatches->isEmpty()) {
                    return $query;
                }

                return $query->where(function (Builder $builder) use ($normalizedMatches): void {
                    $normalizedMatches->each(function (string $value, int $index) use ($builder): void {
                        $clause = static function (Builder $inner) use ($value): void {
                            $inner->where('ip_address', $value)
                                ->orWhere('ip_address_hash', $value);
                        };

                        if ($index === 0) {
                            $builder->where($clause);
                        } else {
                            $builder->orWhere($clause);
                        }
                    });
                });
            })
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
        $wasExisting = $alert->exists;

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
            'ip_address_hash' => $activity->ip_address_hash,
            'device_hash' => $sourceType === 'device' ? $identifier : $activity->device_hash,
            'user_ids' => $userIds->all(),
            'occurrences' => $occurrences,
            'window_started_at' => $activities->first()->logged_at ?? $activities->first()->created_at,
            'last_seen_at' => $activities->last()->logged_at ?? $activities->last()->created_at,
            'severity' => $severity ?? MultiAccountAlertSeverity::Low,
            'status' => $status,
            'suppression_reason' => $suppressionReason,
            'metadata' => $this->buildMetadata($sourceType, $identifier, $activities, $vpnSuspected, $normalizedMatches->all()),
        ])->save();

        $this->metrics->increment('security.multi_account_alert', 1.0, [
            'state' => $wasExisting ? 'updated' : 'created',
            'source' => $sourceType,
            'severity' => $alert->severity?->value ?? 'unknown',
            'status' => $status->value,
            'suppressed' => $status === MultiAccountAlertStatus::Suppressed ? 'yes' : 'no',
        ]);

        if ($status === MultiAccountAlertStatus::Open) {
            $this->maybeNotify($alert);
        }
    }

    public function withoutNotifications(callable $callback): void
    {
        $previous = $this->suppressNotifications;
        $this->suppressNotifications = true;

        try {
            $callback($this);
        } finally {
            $this->suppressNotifications = $previous;
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
     * @param EloquentCollection<int, LoginActivity> $activities
     * @return array<string, mixed>
     */
    protected function buildMetadata(string $sourceType, string $identifier, EloquentCollection $activities, bool $vpnSuspected, array $matchIdentifiers): array
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
                    'ip_address_hash' => $activity->ip_address_hash,
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
                'identifiers' => array_values(array_unique(array_filter($matchIdentifiers))),
            ],
            'vpn_suspected' => $vpnSuspected,
            'user_counts' => $countsByUser,
            'recent_timeline' => $timeline,
        ];
    }

    protected function maybeNotify(MultiAccountAlert $alert): void
    {
        if ($this->suppressNotifications) {
            return;
        }

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
