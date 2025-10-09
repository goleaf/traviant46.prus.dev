<?php

namespace App\Jobs;

use Core\Database\DB;

class ProcessResearchCompletion
{
    public function handle(): void
    {
        $db = DB::getInstance();
        $result = $db->query(
            "SELECT id, kid, nr, mode FROM research WHERE end_time <= " . time() . " ORDER BY end_time ASC, id ASC LIMIT 100"
        );

        while ($row = $result->fetch_assoc()) {
            $db->query("DELETE FROM research WHERE id={$row['id']}");
            if ($row['mode'] == 1) {
                $db->query("UPDATE tdata SET u{$row['nr']}=1 WHERE kid={$row['kid']}");
            } else {
                $db->query(
                    "UPDATE smithy SET u{$row['nr']}=IF(u{$row['nr']}+1>20, 20, u{$row['nr']}+1) WHERE kid={$row['kid']}" 
                );
            }
        }
    }
}
