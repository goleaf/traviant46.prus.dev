<?php

namespace App\Jobs;

use Core\Automation;

class CheckGameFinish
{
    public function runAction()
    {
        Automation::getInstance()->checkGameFinish();
    }
}
