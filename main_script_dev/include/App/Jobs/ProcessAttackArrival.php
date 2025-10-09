<?php

namespace App\Jobs;

use Core\Database\DB;
use Model\BattleModel;
use Model\Movements\AdventureProcessor;
use Model\Movements\EvasionProcessor;
use Model\Movements\SettlersProcessor;
use Model\MovementsModel;
use function logError;

class ProcessAttackArrival extends MovementJob
{
    protected function fetchMovements(DB $db)
    {
        $attackTypes = [
            MovementsModel::ATTACKTYPE_EVASION,
            MovementsModel::ATTACKTYPE_NORMAL,
            MovementsModel::ATTACKTYPE_RAID,
            MovementsModel::ATTACKTYPE_SPY,
            MovementsModel::ATTACKTYPE_ADVENTURE,
            MovementsModel::ATTACKTYPE_SETTLERS,
        ];

        $sql = sprintf(
            'SELECT * FROM movement WHERE mode=0 AND attack_type IN (%s) AND end_time <= %d ORDER BY end_time ASC, id ASC LIMIT %d',
            implode(',', $attackTypes),
            $this->getCutoff(),
            $this->getLimit()
        );

        return $db->query($sql);
    }

    protected function processRow(array $row): void
    {
        switch ((int)$row['attack_type']) {
            case MovementsModel::ATTACKTYPE_EVASION:
                new EvasionProcessor($row);
                break;
            case MovementsModel::ATTACKTYPE_NORMAL:
            case MovementsModel::ATTACKTYPE_RAID:
            case MovementsModel::ATTACKTYPE_SPY:
                new BattleModel($row);
                break;
            case MovementsModel::ATTACKTYPE_ADVENTURE:
                new AdventureProcessor($row);
                break;
            case MovementsModel::ATTACKTYPE_SETTLERS:
                new SettlersProcessor($row);
                break;
            default:
                logError('Unknown attack type in ProcessAttackArrival: ' . $row['attack_type']);
        }
    }
}
