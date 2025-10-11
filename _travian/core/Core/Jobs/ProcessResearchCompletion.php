<?php

namespace Core\Jobs;

use Core\Database\DB;

class ProcessResearchCompletion
{
    public function __construct(private readonly int $chunkSize = 100)
    {
    }

    public function handle(): void
    {
        $db = DB::getInstance();
        $now = time();

        $result = $db->query(
            sprintf(
                'SELECT id, kid, nr, mode FROM research WHERE end_time <= %d ORDER BY end_time ASC, id ASC LIMIT %d',
                $now,
                $this->chunkSize
            )
        );

        if ($result === false) {
            return;
        }

        while ($row = $result->fetch_assoc()) {
            $db->query(sprintf('DELETE FROM research WHERE id=%d', (int) $row['id']));

            if ($db->affectedRows() === 0) {
                continue;
            }

            if ((int) $row['mode'] === 1) {
                $db->query(
                    sprintf(
                        'UPDATE tdata SET u%d=1 WHERE kid=%d',
                        (int) $row['nr'],
                        (int) $row['kid']
                    )
                );
                continue;
            }

            $db->query(
                sprintf(
                    'UPDATE smithy SET u%d=IF(u%d+1>20, 20, u%d+1) WHERE kid=%d',
                    (int) $row['nr'],
                    (int) $row['nr'],
                    (int) $row['nr'],
                    (int) $row['kid']
                )
            );
        }
    }
}
