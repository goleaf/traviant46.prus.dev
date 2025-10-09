<?php

namespace App\Jobs;

use Model\BattleModel;
use Model\Movements\AdventureProcessor;
use Model\Movements\EvasionProcessor;
use Model\Movements\SettlersProcessor;
use Model\MovementsModel;

class ProcessAttackArrival
{
    /**
     * @var array
     */
    private $movement;

    public function __construct(array $movement)
    {
        $this->movement = $movement;
    }

    public function handle()
    {
        switch ($this->movement['attack_type']) {
            case MovementsModel::ATTACKTYPE_EVASION:
                new EvasionProcessor($this->movement);
                break;
            case MovementsModel::ATTACKTYPE_NORMAL:
            case MovementsModel::ATTACKTYPE_RAID:
            case MovementsModel::ATTACKTYPE_SPY:
                new BattleModel($this->movement);
                break;
            case MovementsModel::ATTACKTYPE_ADVENTURE:
                new AdventureProcessor($this->movement);
                break;
            case MovementsModel::ATTACKTYPE_SETTLERS:
                new SettlersProcessor($this->movement);
                break;
        }
    }
}
