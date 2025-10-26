<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Enums\AccountDeletionRequestStatus;
use App\Jobs\GenerateUserDataExport;
use App\Jobs\ProcessAccountDeletionRequest;
use App\Models\AccountDeletionRequest;
use App\Models\User;
use App\Models\UserDataExport;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PrivacyActionService
{
    public function __construct(
        protected AuditLogger $auditLogger,
        protected IpAnonymizer $ipAnonymizer,
    ) {}

    public function requestExport(User $user, ?string $ipAddress = null): UserDataExport
    {
        $maxActive = (int) config('privacy.export.max_active_requests', 1);
        $activeCount = $user->dataExports()
            ->whereIn('status', [
                UserDataExport::STATUS_PENDING,
                UserDataExport::STATUS_PROCESSING,
            ])
            ->count();

        if ($activeCount >= $maxActive) {
            throw new RuntimeException(__('You already have an export request in progress.'));
        }

        return DB::transaction(function () use ($user, $ipAddress) {
            $export = $user->dataExports()->create([
                'status' => UserDataExport::STATUS_PENDING,
                'disk' => config('privacy.export.storage_disk', 'local'),
                'requested_at' => Carbon::now(),
                'expires_at' => Carbon::now()->addDays((int) config('privacy.export.expires_after_days', 14)),
            ]);

            $this->auditLogger->log($user, 'privacy.export.requested', [
                'export_id' => $export->getKey(),
            ], null, $ipAddress);

            GenerateUserDataExport::dispatch($export->getKey());

            return $export;
        });
    }

    public function requestDeletion(User $user, ?string $notes, string $ipAddress): AccountDeletionRequest
    {
        $pending = $user->deletionRequests()
            ->whereIn('status', [AccountDeletionRequestStatus::Pending->value, AccountDeletionRequestStatus::InProgress->value])
            ->first();

        if ($pending !== null) {
            throw new RuntimeException(__('You already have an active account deletion request.'));
        }

        $cooldownDays = (int) config('privacy.deletion.cooldown_days', 7);
        $graceMinutes = (int) config('privacy.deletion.grace_minutes', 5);
        $scheduledFor = Carbon::now()->addDays($cooldownDays)->addMinutes($graceMinutes);

        return DB::transaction(function () use ($user, $notes, $ipAddress, $scheduledFor) {
            $request = $user->deletionRequests()->create([
                'status' => AccountDeletionRequestStatus::Pending,
                'requested_at' => Carbon::now(),
                'scheduled_for' => $scheduledFor,
                'notes' => $notes,
                'request_ip' => $ipAddress,
                'request_ip_hash' => $this->ipAnonymizer->anonymize($ipAddress),
            ]);

            $this->auditLogger->log($user, 'privacy.deletion.requested', [
                'deletion_request_id' => $request->getKey(),
                'scheduled_for' => $scheduledFor->toAtomString(),
            ], $request, $ipAddress);

            ProcessAccountDeletionRequest::dispatch($request->getKey())->delay($scheduledFor);

            return $request;
        });
    }

    public function cancelDeletion(User $user): ?AccountDeletionRequest
    {
        $pending = $user->deletionRequests()
            ->whereIn('status', [AccountDeletionRequestStatus::Pending->value, AccountDeletionRequestStatus::InProgress->value])
            ->latest('requested_at')
            ->first();

        if ($pending === null) {
            return null;
        }

        $pending->forceFill([
            'status' => AccountDeletionRequestStatus::Cancelled,
            'cancelled_at' => Carbon::now(),
        ])->save();

        $this->auditLogger->log($user, 'privacy.deletion.cancelled', [
            'deletion_request_id' => $pending->getKey(),
        ]);

        return $pending;
    }
}
