<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Enums\MultiAccountAlertSeverity;
use App\Enums\MultiAccountAlertStatus;
use App\Models\LoginActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class MultiAccountDetector
{
    public function __construct(
        protected MultiAccountRules $rules,
        protected MultiAccountAlertsService $alerts,
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

        $worldId = is_string($activity->world_id) ? trim($activity->world_id) : null;

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
            ->when($worldId !== null && $worldId !== '', fn (Builder $query) => $query->where('world_id', $worldId))
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

        $groupKey = $this->buildGroupKey($sourceType, $identifier, $userIds, $worldId);
        $status = $suppressionReason !== null
            ? MultiAccountAlertStatus::Suppressed
            : MultiAccountAlertStatus::Open;

        $firstActivity = $activities->first();
        $lastActivity = $activities->last();

        $windowStartedAt = $firstActivity?->logged_at ?? $firstActivity?->created_at;
        $lastSeenAt = $lastActivity?->logged_at ?? $lastActivity?->created_at;
        $firstSeenAt = $firstActivity?->logged_at ?? $firstActivity?->created_at;

        $this->alerts->upsert(
            groupKey: $groupKey,
            sourceType: $sourceType,
            worldId: $worldId,
            ipAddress: $activity->ip_address,
            ipAddressHash: $activity->ip_address_hash,
            deviceHash: $sourceType === 'device' ? $identifier : $activity->device_hash,
            userIds: $userIds->all(),
            occurrences: $occurrences,
            windowStartedAt: $windowStartedAt,
            firstSeenAt: $firstSeenAt,
            lastSeenAt: $lastSeenAt,
            severity: $severity,
            status: $status,
            suppressionReason: $suppressionReason,
            metadata: $this->buildMetadata(
                $sourceType,
                $identifier,
                $activities,
                $vpnSuspected,
                $normalizedMatches->all(),
                $worldId,
            ),
            shouldNotify: $status === MultiAccountAlertStatus::Open,
        );
    }

    public function withoutNotifications(callable $callback): void
    {
        $this->alerts->withoutNotifications(function () use ($callback): void {
            $callback($this);
        });
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

    protected function buildGroupKey(string $sourceType, string $identifier, Collection $userIds, ?string $worldId): string
    {
        $context = $worldId !== null && $worldId !== '' ? $worldId : 'global';

        return sha1($context.'|'.$sourceType.'|'.$identifier.'|'.implode('-', $userIds->all()));
    }

    /**
     * @param EloquentCollection<int, LoginActivity> $activities
     * @return array<string, mixed>
     */
    protected function buildMetadata(string $sourceType, string $identifier, EloquentCollection $activities, bool $vpnSuspected, array $matchIdentifiers, ?string $worldId): array
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
                    'world_id' => $activity->world_id,
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
            'world_id' => $worldId,
            'user_counts' => $countsByUser,
            'recent_timeline' => $timeline,
        ];
    }
}
