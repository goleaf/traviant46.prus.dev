<?php

namespace App\Jobs;

use Core\Database\DB;
use function make_seed;
use function miliseconds;

abstract class MovementJob
{
    /**
     * The maximum number of movements to process in a single run.
     */
    private $limit;

    public function __construct(int $limit = 250)
    {
        $this->limit = max(1, $limit);
    }

    final public function handle(): void
    {
        $db = DB::getInstance();
        $movements = $this->fetchMovements($db);

        if (!($movements instanceof \mysqli_result)) {
            return;
        }

        mt_srand(make_seed());

        while ($row = $movements->fetch_assoc()) {
            $db->query("DELETE FROM movement WHERE id={$row['id']}");
            if (!$db->affectedRows()) {
                continue;
            }

            $this->processRow($row);
        }
    }

    protected function getLimit(): int
    {
        return $this->limit;
    }

    protected function getCutoff(): int
    {
        return miliseconds();
    }

    /**
     * Fetch the movements that should be processed by the job.
     */
    abstract protected function fetchMovements(DB $db);

    /**
     * Handle an individual movement row.
     */
    abstract protected function processRow(array $row): void;
}
