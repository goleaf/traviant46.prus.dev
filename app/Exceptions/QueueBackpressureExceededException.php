<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class QueueBackpressureExceededException extends Exception
{
    public static function attempts(string $jobName, int $maxAttempts): self
    {
        return new self(sprintf('Job %s exceeded maximum attempts (%d).', $jobName, $maxAttempts));
    }
}
