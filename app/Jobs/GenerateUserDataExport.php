<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\UserDataExport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use JsonException;
use Throwable;

class GenerateUserDataExport implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        protected int $exportId,
    ) {}

    /**
     * @throws JsonException
     */
    public function handle(): void
    {
        /** @var UserDataExport|null $export */
        $export = UserDataExport::query()
            ->with('user')
            ->find($this->exportId);

        if (! $export instanceof UserDataExport || $export->user === null) {
            return;
        }

        $export->forceFill([
            'status' => UserDataExport::STATUS_PROCESSING,
        ])->save();

        try {
            $payload = $this->buildPayload($export);
            $disk = $export->disk ?? config('privacy.export.storage_disk', 'local');
            $directory = trim(config('privacy.export.storage_path', 'privacy/exports'), '/');
            $filename = $directory.'/export-'.$export->getKey().'-'.Str::uuid().'.json';
            $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);

            Storage::disk($disk)->put($filename, $json);

            $size = Storage::disk($disk)->size($filename) ?: strlen((string) $json);
            $recordCount = $this->countRecords($payload);

            $export->forceFill([
                'status' => UserDataExport::STATUS_COMPLETED,
                'disk' => $disk,
                'file_path' => $filename,
                'size_bytes' => $size,
                'record_count' => $recordCount,
                'completed_at' => Carbon::now(),
                'failure_reason' => null,
            ])->save();
        } catch (Throwable $exception) {
            $export->forceFill([
                'status' => UserDataExport::STATUS_FAILED,
                'failure_reason' => $exception->getMessage(),
                'completed_at' => Carbon::now(),
            ])->save();

            throw $exception;
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildPayload(UserDataExport $export): array
    {
        $user = $export->user;

        $baseUser = Arr::except($user->toArray(), [
            'password',
            'remember_token',
            'two_factor_secret',
            'two_factor_recovery_codes',
        ]);

        $villages = $user->villages()
            ->select(['id', 'name', 'population', 'loyalty', 'x_coordinate', 'y_coordinate', 'is_capital', 'created_at', 'updated_at'])
            ->orderBy('created_at')
            ->get()
            ->toArray();

        $loginActivities = $user->loginActivities()
            ->latest('logged_at')
            ->limit(50)
            ->get(['id', 'ip_address', 'ip_address_hash', 'device_hash', 'via_sitter', 'logged_at', 'created_at'])
            ->toArray();

        $loginIpLogs = $user->loginIpLogs()
            ->latest('recorded_at')
            ->limit(50)
            ->get(['id', 'ip_address', 'ip_address_hash', 'recorded_at', 'created_at'])
            ->toArray();

        return [
            'generated_at' => Carbon::now()->toAtomString(),
            'user' => $baseUser,
            'villages' => $villages,
            'login_activities' => $loginActivities,
            'login_ip_logs' => $loginIpLogs,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    protected function countRecords(array $payload): int
    {
        $counts = [
            count(Arr::get($payload, 'villages', [])),
            count(Arr::get($payload, 'login_activities', [])),
            count(Arr::get($payload, 'login_ip_logs', [])),
        ];

        return array_sum($counts) + 1; // include user profile
    }
}
