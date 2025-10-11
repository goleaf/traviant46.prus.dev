<?php

namespace Core\Jobs;

use Core\Database\DB;
use Game\Buildings\BuildingAction;

class ProcessDemolition
{
    public function __construct(private readonly int $chunkSize = 50)
    {
    }

    public function handle(): void
    {
        $db = DB::getInstance();
        $now = time();

        $result = $db->query(
            sprintf(
                'SELECT id, kid, building_field, complete FROM demolition WHERE end_time <= %d ORDER BY end_time ASC, id ASC LIMIT %d',
                $now,
                $this->chunkSize
            )
        );

        if ($result === false) {
            return;
        }

        while ($row = $result->fetch_assoc()) {
            $db->query(sprintf('DELETE FROM demolition WHERE id=%d', (int) $row['id']));

            if ($db->affectedRows() === 0) {
                continue;
            }

            BuildingAction::downgrade(
                (int) $row['kid'],
                (int) $row['building_field'],
                1,
                (int) $row['complete']
            );
        }
    }
}
