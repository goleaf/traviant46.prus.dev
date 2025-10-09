<?php

namespace Core\Jobs;

use Core\Queue\QueueManager;

class Job
{
    private string $name;
    private int $interval;
    /** @var array<string, mixed> */
    private array $callback;

    public function __construct(string $name, int $interval, array $callback)
    {
        $this->name = $name;
        $this->interval = $interval;
        $this->callback = $callback;
    }

    public static function automation(string $name, int $interval, string $method): self
    {
        return new self($name, $interval, [
            'type' => 'automation',
            'method' => $method,
        ]);
    }

    public static function model(string $name, int $interval, string $class, string $method): self
    {
        return new self($name, $interval, [
            'type' => 'model',
            'class' => $class,
            'method' => $method,
        ]);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getInterval(): int
    {
        return $this->interval;
    }

    /**
     * @return array<string, mixed>
     */
    public function getCallback(): array
    {
        return $this->callback;
    }

    public function dispatch(): void
    {
        QueueManager::dispatchRecurring($this);
    }
}
