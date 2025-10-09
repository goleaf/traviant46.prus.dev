<?php

namespace App\Jobs;

use Core\Database\DB;
use Model\Movements\ReturnProcessor;

class ProcessReturnMovement extends MovementJob
{
    protected function fetchMovements(DB $db)
    {
        $sql = sprintf(
            'SELECT * FROM movement WHERE mode=1 AND end_time <= %d ORDER BY end_time ASC, id ASC LIMIT %d',
            $this->getCutoff(),
            $this->getLimit()
        );

        return $db->query($sql);
    }

    protected function processRow(array $row): void
    {
        new ReturnProcessor($row);
    }
}
