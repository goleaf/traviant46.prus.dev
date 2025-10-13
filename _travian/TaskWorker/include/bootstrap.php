<?php

use App\ValueObjects\Travian\TaskWorkerRuntime;
use Illuminate\Contracts\Foundation\Application;

$projectRoot = dirname(__DIR__, 2);

require $projectRoot . '/vendor/autoload.php';

/** @var Application $app */
$app = require $projectRoot . '/bootstrap/app.php';
$app->boot();

$runtime = TaskWorkerRuntime::resolve();

spl_autoload_register(static function (string $className) use ($runtime): bool {
    $filePath = $runtime->includePath() . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $className) . '.php';
    if (is_file($filePath)) {
        include $filePath;
        return true;
    }

    return false;
});

require $runtime->includePath() . 'vendor/autoload.php';
require $runtime->includePath() . 'ClouDNS_SDK.php';
require $runtime->includePath() . 'functions.php';
