<?php

namespace App\Jobs;

use Core\Database\DB;
use Game\Buildings\BuildingAction;
use Model\MasterBuilder;

class ProcessBuildingCompletion
{
    public function handle(): void
    {
        $db = DB::getInstance();
        $this->processBuildingQueue($db);
        $this->processDemolitionQueue($db);
    }

    private function processBuildingQueue(DB $db): void
    {
        $masterBuilder = new MasterBuilder();
        $result = $db->query(
            "SELECT * FROM building_upgrade WHERE commence<=" . time() . " ORDER BY commence ASC, id ASC LIMIT 100"
        );

        while ($row = $result->fetch_assoc()) {
            if ($row['isMaster']) {
                $masterBuilder->process($row);
                continue;
            }

            $db->query("DELETE FROM building_upgrade WHERE id={$row['id']}");
            if ($db->affectedRows()) {
                BuildingAction::upgrade($row['kid'], $row['building_field']);
            }
        }
    }

    private function processDemolitionQueue(DB $db): void
    {
        $result = $db->query(
            "SELECT * FROM demolition WHERE end_time <= " . time() . " ORDER BY end_time ASC, id ASC LIMIT 50"
        );

        while ($row = $result->fetch_assoc()) {
            $db->query("DELETE FROM demolition WHERE id={$row['id']}");
            if ($db->affectedRows()) {
                BuildingAction::downgrade($row['kid'], $row['building_field'], 1, $row['complete']);
            }
        }
    }
}
