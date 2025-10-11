<?php

namespace App\Services\Maintenance;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Config;

class MessageCleanupService
{
    public function __construct(private readonly DatabaseManager $db)
    {
    }

    public function handle(): array
    {
        $now = now()->timestamp;
        $retention = max(0, (int) Config::get('maintenance.messages.retention_seconds', 0));
        $batch = max(1, (int) Config::get('maintenance.messages.batch', 5000));

        $cutoff = $now - $retention;
        $deleted = $this->legacy()->affectingStatement(
            sprintf(
                'DELETE FROM mdata WHERE viewed = 1 AND time < ? LIMIT %d',
                $batch
            ),
            [$cutoff]
        );

        return [
            'deleted' => $deleted,
            'cutoff' => $cutoff,
        ];
    }

    protected function legacy(): ConnectionInterface
    {
        $connection = Config::get('maintenance.connections.legacy', 'legacy');

        return $this->db->connection($connection);
    }
}
