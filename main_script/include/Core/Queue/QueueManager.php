<?php

namespace Core\Queue;

use Core\Jobs\Job;
use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Queue\Capsule\Manager as QueueCapsule;
use Illuminate\Queue\QueueManager as IlluminateQueueManager;
use Illuminate\Redis\RedisManager;
use RedisException;

class QueueManager
{
    private static ?QueueCapsule $capsule = null;
    private static ?Container $container = null;
    private static ?RedisManager $redis = null;

    public static function boot(): void
    {
        if (self::$capsule instanceof QueueCapsule) {
            return;
        }

        $container = new Container();
        $events = new Dispatcher($container);
        $container->instance('events', $events);

        $host = getenv('REDIS_HOST') ?: '127.0.0.1';
        $password = getenv('REDIS_PASSWORD') ?: null;
        $port = (int)(getenv('REDIS_PORT') ?: 6379);
        $database = (int)(getenv('REDIS_QUEUE_DB') ?: 2);

        $redisConfig = [
            'client' => 'phpredis',
            'default' => [
                'host' => $host,
                'password' => $password !== '' ? $password : null,
                'port' => $port,
                'database' => $database,
            ],
        ];

        $redis = new RedisManager($container, $redisConfig['client'], $redisConfig);
        $container->instance('redis', $redis);

        $capsule = new QueueCapsule($container);
        $capsule->addConnection([
            'driver' => 'redis',
            'connection' => 'default',
            'queue' => getenv('AUTOMATION_QUEUE_NAME') ?: 'automation',
            'retry_after' => (int)(getenv('QUEUE_RETRY_AFTER') ?: 90),
            'block_for' => null,
        ], 'automation');

        $capsule->setAsGlobal();
        $capsule->setEventDispatcher($events);

        self::$capsule = $capsule;
        self::$container = $container;
        self::$redis = $redis;
    }

    public static function container(): Container
    {
        self::boot();
        return self::$container;
    }

    public static function queue(): IlluminateQueueManager
    {
        self::boot();
        return self::$capsule->getQueueManager();
    }

    public static function dispatchRecurring(Job $job): void
    {
        self::boot();
        $lockKey = self::lockKey($job->getName());
        $ttl = self::lockTtl($job);
        $connection = self::$redis->connection();

        try {
            $wasQueued = $connection->set($lockKey, 'queued', ['nx' => true, 'ex' => $ttl]);
        } catch (RedisException $exception) {
            return;
        }

        if (!$wasQueued) {
            return;
        }

        self::pushJob($job);
    }

    public static function scheduleRecurring(Job $job): void
    {
        self::boot();
        $lockKey = self::lockKey($job->getName());
        $ttl = self::lockTtl($job);

        try {
            self::$redis->connection()->set($lockKey, 'queued', ['ex' => $ttl]);
        } catch (RedisException $exception) {
            return;
        }

        self::pushJob($job);
    }

    public static function releaseRecurring(string $jobName): void
    {
        self::boot();
        try {
            self::$redis->connection()->del(self::lockKey($jobName));
        } catch (RedisException $exception) {
        }
    }

    private static function pushJob(Job $job): void
    {
        $delay = max($job->getInterval(), 0);
        self::queue()->connection('automation')->later($delay, new \App\Jobs\RecurringCallbackJob($job));
    }

    private static function lockKey(string $jobName): string
    {
        return sprintf('automation:recurring:%s', $jobName);
    }

    private static function lockTtl(Job $job): int
    {
        $interval = max($job->getInterval(), 0);
        return max($interval + 30, 60);
    }

}
