<?php

namespace App\Jobs;

use Model\MedalsModel;

class ProcessMedals
{
    public function runAction()
    {
        (new MedalsModel())->resetMedals();
    }
}
