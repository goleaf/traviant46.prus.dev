<?php

namespace App\Jobs;

use Core\Automation;

class ProcessAllianceBonus
{
    public function runAction()
    {
        Automation::getInstance()->handleAllianceBonusTasks();
    }
}
