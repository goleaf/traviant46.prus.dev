<?php
set_time_limit(0);
ini_set('mysql.connect_timeout', '0');
ini_set('max_execution_time', '0');
declare(ticks=1);

use Core\ErrorHandler;
use Core\Jobs;
use Core\Jobs\JobScheduler;

require(__DIR__ . "/bootstrap.php");
$automationLogFile = dirname(ERROR_LOG_FILE) . "/automation.log";
$processControl = function_exists('pcntl_fork') && function_exists('posix_setsid') && function_exists('pcntl_signal') && function_exists('posix_kill');

global $PIDs, $loop;
$PIDs = [];

if (!$processControl) {
    Jobs\Launcher::lunchJobs();
    JobScheduler::getInstance()->run();
    return;
}

$autoPID = pcntl_fork();
fclose(STDIN);
fclose(STDOUT);
fclose(STDERR);
$STDIN = fopen('/dev/null', 'r');
$STDOUT = fopen($automationLogFile, 'wb');
$STDERR = fopen($automationLogFile, 'wb');
if ($autoPID) {
    exit(0);
} elseif ($autoPID == -1) {
    exit(1);
} else {
    $loop = TRUE;
    $newSID = posix_setsid();
    if ($newSID === -1) {
        exit(1);
    }
    function sig_handler($signal)
    {
        global $PIDs, $loop;
        $loop = FALSE;
        foreach ($PIDs as $k => $v) {
            try {
                posix_kill($v, SIGTERM);
                unset($PIDs[$k]);
            } catch (\Exception $e) {
                ErrorHandler::getInstance()->handleExceptions($e);
            }
        }
        exit;
    }

    pcntl_signal(SIGTERM, "sig_handler");
    pcntl_signal(SIGHUP, "sig_handler");
    Jobs\Launcher::lunchJobs();

    while ($loop) {
        sleep(1);
    }
}