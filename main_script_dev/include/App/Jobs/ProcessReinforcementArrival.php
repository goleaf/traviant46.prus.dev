<?php

namespace App\Jobs;

use Model\Movements\ReinforcementProcessor;

class ProcessReinforcementArrival
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
        new ReinforcementProcessor($this->movement);
    }
}
