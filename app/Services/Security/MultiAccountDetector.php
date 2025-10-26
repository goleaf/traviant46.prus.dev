<?php

namespace App\Services\Security;

use App\Models\LoginActivity;
use App\Models\MultiAccountAlert;
use App\Models\User;
use Illuminate\Support\Carbon;

class MultiAccountDetector
{
    public function record(User $user, string $ipAddress, Carbon $timestamp, bool $viaSitter = false, ?int $sitterId = null): void
    {
        if ($ipAddress === '') {
            return;
        }

        $conflictingUserIds = LoginActivity::query()
            ->where('ip_address', $ipAddress)
            ->where('user_id', '!=', $user->getKey())
            ->distinct()
            ->pluck('user_id');

        foreach ($conflictingUserIds as $conflictId) {
            $this->touchAlert($user->getKey(), (int) $conflictId, $ipAddress, $timestamp);
            $this->touchAlert((int) $conflictId, $user->getKey(), $ipAddress, $timestamp);
        }
    }

    protected function touchAlert(int $primaryId, int $conflictId, string $ipAddress, Carbon $timestamp): void
    {
        $alert = MultiAccountAlert::query()->firstOrNew([
            'ip_address' => $ipAddress,
            'primary_user_id' => $primaryId,
            'conflict_user_id' => $conflictId,
        ]);

        $alert->occurrences = $alert->exists ? $alert->occurrences + 1 : 1;
        $alert->last_seen_at = $timestamp;
        $alert->save();
    }
}
