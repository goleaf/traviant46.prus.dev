<?php

declare(strict_types=1);

set_time_limit(0);
ini_set('max_execution_time', '0');

use Core\Queue\QueueManager;
use Illuminate\Queue\Worker;
use Illuminate\Queue\WorkerOptions;

require __DIR__ . '/bootstrap.php';

QueueManager::boot();

function queue_env_bool(string $key, bool $default = false): bool
{
    $value = getenv($key);
    if ($value === false) {
        return $default;
    }

    $filtered = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    return $filtered === null ? $default : $filtered;
}

$container = QueueManager::container();
$worker = new Worker(QueueManager::queue(), $container->make('events'), $container);

$queueName = getenv('AUTOMATION_QUEUE_NAME') ?: 'automation';
$connection = 'automation';
$options = new WorkerOptions(
    name: $queueName,
    backoff: (int)(getenv('QUEUE_BACKOFF') ?: 0),
    memory: (int)(getenv('QUEUE_MEMORY_LIMIT') ?: 256),
    timeout: (int)(getenv('QUEUE_TIMEOUT') ?: 60),
    sleep: (int)(getenv('QUEUE_SLEEP') ?: 1),
    maxTries: (int)(getenv('QUEUE_MAX_TRIES') ?: 1),
    force: queue_env_bool('QUEUE_FORCE'),
    stopWhenEmpty: queue_env_bool('QUEUE_STOP_WHEN_EMPTY'),
    maxJobs: (int)(getenv('QUEUE_MAX_JOBS') ?: 0),
    maxTime: (int)(getenv('QUEUE_MAX_TIME') ?: 0),
    rest: (int)(getenv('QUEUE_REST') ?: 0)
);

$worker->daemon($connection, $queueName, $options);
