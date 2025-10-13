<?php

use App\ValueObjects\Travian\PaymentPaths;
use Illuminate\Contracts\Foundation\Application;

$projectRoot = dirname(__DIR__, 3);

require $projectRoot . '/vendor/autoload.php';

/** @var Application $app */
$app = require $projectRoot . '/bootstrap/app.php';
$app->boot();

$paths = PaymentPaths::resolve();

require $paths->includePath() . 'vendor/autoload.php';
require $paths->includePath() . 'config.inc.php';
require $paths->includePath() . 'functions.general.php';
