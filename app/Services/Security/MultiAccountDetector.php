<?php

namespace App\Services\Security;

use App\Models\LoginActivity;
use App\Models\MultiAccountAlert;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class MultiAccountDetector
{
    public function record(User $user, string $ipAddress, Carbon $timestamp, bool $viaSitter = false, ?int $sitterId = null): void
    {
        if ($ipAddress === '') {
            return;
        }

        $involvedUserIds = LoginActivity::query()
            ->fromIp($ipAddress)
            ->pluck('user_id')
            ->push($user->getKey())
            ->unique()
            ->sort()
            ->values();

        if ($involvedUserIds->count() < 2) {
            return;
        }

        $this->touchAlert($ipAddress, $involvedUserIds, $timestamp);
    }

    protected function touchAlert(string $ipAddress, Collection $userIds, Carbon $timestamp): void
    {
        $groupKey = $this->buildGroupKey($ipAddress, $userIds);

        $alert = MultiAccountAlert::query()->firstOrNew([
            'group_key' => $groupKey,
        ]);

        if (! $alert->exists) {
            $alert->alert_id = (string) Str::uuid();
            $alert->ip_address = $ipAddress;
            $alert->user_ids = $userIds->all();
            $alert->first_seen_at = $timestamp;
        }

        $alert->last_seen_at = $timestamp;
        $alert->severity = $this->determineSeverity($userIds->count(), $timestamp, $alert->first_seen_at);
        $alert->save();
    }

    protected function buildGroupKey(string $ipAddress, Collection $userIds): string
    {
        return sha1($ipAddress.'|'.implode('-', $userIds->all()));
    }

    protected function determineSeverity(int $userCount, Carbon $lastSeen, ?Carbon $firstSeen): string
    {
        if ($userCount >= 4) {
            return 'high';
        }

        if ($userCount === 3) {
            return 'medium';
        }

        if ($firstSeen !== null && $firstSeen->diffInMinutes($lastSeen) <= 60) {
            return 'medium';
        }

        return 'low';
    }
}
