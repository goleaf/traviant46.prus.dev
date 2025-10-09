<?php

namespace App\Jobs;

use Model\Movements\ReturnProcessor;

class ProcessReturnMovement
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
        new ReturnProcessor($this->movement);
    }
}
