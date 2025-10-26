<?php

declare(strict_types=1);

namespace App\Support\Logging;

use Monolog\Logger;

class RedactSensitiveDataTap
{
    public function __invoke(Logger $logger): void
    {
        $logger->pushProcessor(new RedactSensitiveDataProcessor);
    }
}
