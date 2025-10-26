<?php

declare(strict_types=1);

namespace Tests\Stubs;

use Illuminate\Http\Response;

class FakeGameReports
{
    public function __invoke(): Response
    {
        return response()->noContent();
    }
}
