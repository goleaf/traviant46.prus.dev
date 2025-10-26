<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\AccountDeletionRequestStatus;
use App\Models\AccountDeletionRequest;
use App\Services\Security\AuditLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProcessAccountDeletionRequest implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        protected int $requestId,
    ) {}

    public function handle(AuditLogger $auditLogger): void
    {
        /** @var AccountDeletionRequest|null $request */
        $request = AccountDeletionRequest::query()
            ->with('user')
            ->find($this->requestId);

        if (! $request instanceof AccountDeletionRequest) {
            return;
        }

        if (! in_array($request->status, [AccountDeletionRequestStatus::Pending, AccountDeletionRequestStatus::InProgress], true)) {
            return;
        }

        if ($request->scheduled_for !== null && $request->scheduled_for->isFuture()) {
            $this->release($request->scheduled_for->diffInSeconds(Carbon::now()) + 60);

            return;
        }

        $request->forceFill([
            'status' => AccountDeletionRequestStatus::InProgress,
        ])->save();

        $user = $request->user;

        try {
            if ($user !== null) {
                $auditLogger->log($user, 'privacy.deletion.started', [
                    'deletion_request_id' => $request->getKey(),
                ], $request, $request->request_ip);
            }

            DB::transaction(function () use ($user): void {
                if ($user !== null) {
                    $user->delete();
                }
            });

            $request->forceFill([
                'status' => AccountDeletionRequestStatus::Completed,
                'processed_at' => Carbon::now(),
            ])->save();

            $auditLogger->log(null, 'privacy.deletion.completed', [
                'deletion_request_id' => $request->getKey(),
                'actor_user_id' => $user?->getKey(),
            ], $request, $request->request_ip);
        } catch (Throwable $exception) {
            $request->forceFill([
                'status' => AccountDeletionRequestStatus::Pending,
                'notes' => trim(($request->notes ?? '').PHP_EOL.'Error: '.$exception->getMessage()),
            ])->save();

            $auditLogger->log($user, 'privacy.deletion.failed', [
                'deletion_request_id' => $request->getKey(),
                'error' => $exception->getMessage(),
            ], $request, $request->request_ip);

            throw $exception;
        }
    }
}
