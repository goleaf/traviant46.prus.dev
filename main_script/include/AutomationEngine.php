<?php

declare(strict_types=1);

set_time_limit(0);
ini_set('mysql.connect_timeout', '0');
ini_set('max_execution_time', '0');

use Core\Jobs\Launcher;
use Core\Queue\QueueManager;

require __DIR__ . '/bootstrap.php';

QueueManager::boot();
Launcher::lunchJobs();
