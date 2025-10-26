<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Models\LoginActivity;
use App\Models\LoginIpLog;
use App\Models\MultiAccountAlert;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class IpRetentionEnforcer
{
    public function __construct(
        protected IpAnonymizer $anonymizer,
    ) {}

    /**
     * @return array<string, array<string, int>>
     */
    public function enforce(): array
    {
        return [
            'login_activities' => $this->processLoginActivities(),
            'login_ip_logs' => $this->processLoginIpLogs(),
            'multi_account_alerts' => $this->processMultiAccountAlerts(),
        ];
    }

    /**
     * @return array<string, int>
     */
    protected function processLoginActivities(): array
    {
        $config = config('privacy.ip.retention.login_activities', []);

        $backfilled = $this->backfillHashes(LoginActivity::query(), 'ip_address', 'ip_address_hash');
        $anonymized = $this->anonymizePlaintext(
            LoginActivity::query(),
            'logged_at',
            'ip_address',
            'ip_address_hash',
            (int) Arr::get($config, 'retain_plaintext_for_days', 30),
        );
        $deleted = $this->purgeRecords(
            LoginActivity::query(),
            'logged_at',
            (int) Arr::get($config, 'delete_after_days', 365),
        );

        return compact('backfilled', 'anonymized', 'deleted');
    }

    /**
     * @return array<string, int>
     */
    protected function processLoginIpLogs(): array
    {
        $config = config('privacy.ip.retention.login_ip_logs', []);

        $backfilled = $this->backfillHashes(LoginIpLog::query(), 'ip_address', 'ip_address_hash');
        $anonymized = $this->anonymizePlaintext(
            LoginIpLog::query(),
            'recorded_at',
            'ip_address',
            'ip_address_hash',
            (int) Arr::get($config, 'retain_plaintext_for_days', 90),
        );
        $deleted = $this->purgeRecords(
            LoginIpLog::query(),
            'recorded_at',
            (int) Arr::get($config, 'delete_after_days', 730),
        );

        return compact('backfilled', 'anonymized', 'deleted');
    }

    /**
     * @return array<string, int>
     */
    protected function processMultiAccountAlerts(): array
    {
        $config = config('privacy.ip.retention.multi_account_alerts', []);

        $backfilled = $this->backfillHashes(MultiAccountAlert::query(), 'ip_address', 'ip_address_hash');
        $anonymized = $this->anonymizePlaintext(
            MultiAccountAlert::query(),
            'last_seen_at',
            'ip_address',
            'ip_address_hash',
            (int) Arr::get($config, 'retain_plaintext_for_days', 60),
        );
        $deleted = $this->purgeRecords(
            MultiAccountAlert::query(),
            'last_seen_at',
            (int) Arr::get($config, 'delete_after_days', 365),
        );

        return compact('backfilled', 'anonymized', 'deleted');
    }

    protected function backfillHashes(Builder $builder, string $ipColumn, string $hashColumn): int
    {
        $updated = 0;
        $batchSize = (int) config('privacy.ip.batch_size', 500);

        $builder
            ->clone()
            ->whereNull($hashColumn)
            ->whereNotNull($ipColumn)
            ->orderBy('id')
            ->chunkById($batchSize, function (Collection $records) use (&$updated, $hashColumn, $ipColumn): void {
                foreach ($records as $record) {
                    $hash = $this->anonymizer->anonymize((string) $record->{$ipColumn});
                    if ($hash === null) {
                        continue;
                    }

                    $record->{$hashColumn} = $hash;
                    $record->save();
                    $updated++;
                }
            });

        return $updated;
    }

    protected function anonymizePlaintext(
        Builder $builder,
        string $timestampColumn,
        string $ipColumn,
        string $hashColumn,
        int $retainDays,
    ): int {
        if ($retainDays <= 0) {
            return 0;
        }

        $threshold = Carbon::now()->subDays($retainDays);
        $batchSize = (int) config('privacy.ip.batch_size', 500);
        $affected = 0;

        $builder
            ->clone()
            ->whereNotNull($ipColumn)
            ->whereNotNull($hashColumn)
            ->where($timestampColumn, '<', $threshold)
            ->orderBy('id')
            ->chunkById($batchSize, function (Collection $records) use (&$affected, $ipColumn): void {
                $ids = $records->pluck('id')->all();
                if ($ids === []) {
                    return;
                }

                $affected += count($ids);

                /** @var Model $first */
                $first = $records->first();

                DB::table($first->getTable())
                    ->whereIn('id', $ids)
                    ->update([
                        $ipColumn => null,
                        'updated_at' => Carbon::now(),
                    ]);
            });

        return $affected;
    }

    protected function purgeRecords(Builder $builder, string $timestampColumn, int $deleteAfterDays): int
    {
        if ($deleteAfterDays <= 0) {
            return 0;
        }

        $threshold = Carbon::now()->subDays($deleteAfterDays);

        return $builder
            ->clone()
            ->where($timestampColumn, '<', $threshold)
            ->delete();
    }
}
