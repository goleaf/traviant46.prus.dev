<?php

namespace App\Services\Maintenance;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;

class ReportCleanupService
{
    public function __construct(private readonly DatabaseManager $db)
    {
    }

    public function handle(): array
    {
        $now = now()->timestamp;
        $batch = max(1, (int) Config::get('maintenance.reports.batch', 20000));

        $deletedDeleted = $this->deleteSoftDeletedReports($now, $batch);
        $deletedUnarchived = $this->deleteExpiredReports($now, $batch);
        $deletedLowLoss = $this->deleteLowLossReports($now, $batch);

        return [
            'deleted_soft_deleted' => $deletedDeleted,
            'deleted_unarchived' => $deletedUnarchived,
            'deleted_low_loss' => $deletedLowLoss,
        ];
    }

    protected function deleteSoftDeletedReports(int $now, int $batch): int
    {
        $retention = max(0, (int) Config::get('maintenance.reports.deleted_retention_seconds', 12 * 3600));
        $cutoff = $now - $retention;

        return $this->legacy()->affectingStatement(
            sprintf(
                'DELETE FROM ndata WHERE non_deletable = 0 AND (uid = 1 OR (deleted = 1 AND time < ?)) LIMIT %d',
                $batch
            ),
            [$cutoff]
        );
    }

    protected function deleteExpiredReports(int $now, int $batch): int
    {
        $retention = max(0, (int) Config::get('maintenance.reports.retention_seconds', 0));
        if ($retention === 0) {
            return 0;
        }

        $cutoff = $now - $retention;

        return $this->legacy()->affectingStatement(
            sprintf(
                'DELETE FROM ndata WHERE non_deletable = 0 AND archive = 0 AND time < ? LIMIT %d',
                $batch
            ),
            [$cutoff]
        );
    }

    protected function deleteLowLossReports(int $now, int $batch): int
    {
        if (!Config::get('maintenance.reports.remove_low_losses', true)) {
            return 0;
        }

        $types = Arr::wrap(Config::get('maintenance.reports.low_loss_types', []));
        if ($types === []) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($types), '?'));
        $cutoff = $now - max(0, (int) Config::get('maintenance.reports.low_loss_grace_seconds', 600));
        $lossThreshold = max(0, (int) Config::get('maintenance.reports.loss_threshold_percent', 30));

        $bindings = array_map('intval', $types);
        $bindings[] = $cutoff;
        $bindings[] = $lossThreshold;

        return $this->legacy()->affectingStatement(
            sprintf(
                'DELETE FROM ndata WHERE non_deletable = 0 AND type IN (%s) AND time < ? AND losses <= ? AND archive = 0 LIMIT %d',
                $placeholders,
                $batch
            ),
            $bindings
        );
    }

    protected function legacy(): ConnectionInterface
    {
        $connection = Config::get('maintenance.connections.legacy', 'legacy');

        return $this->db->connection($connection);
    }
}
