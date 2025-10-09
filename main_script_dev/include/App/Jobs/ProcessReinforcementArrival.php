<?php

namespace App\Jobs;

use Core\Database\DB;
use Model\Movements\ReinforcementProcessor;
use Model\MovementsModel;

class ProcessReinforcementArrival extends MovementJob
{
    protected function fetchMovements(DB $db)
    {
        $sql = sprintf(
            'SELECT * FROM movement WHERE mode=0 AND attack_type=%d AND end_time <= %d ORDER BY end_time ASC, id ASC LIMIT %d',
            MovementsModel::ATTACKTYPE_REINFORCEMENT,
            $this->getCutoff(),
            $this->getLimit()
        );

        return $db->query($sql);
    }

    protected function processRow(array $row): void
    {
        new ReinforcementProcessor($row);
    }
}
