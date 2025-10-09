<?php

namespace App\Jobs;

use Core\Automation;

class BackupDatabase
{
    private Automation $automation;

    public function __construct(?Automation $automation = null)
    {
        $this->automation = $automation ?? Automation::getInstance();
    }

    public function handle(): void
    {
        $this->automation->backup();
    }
}
