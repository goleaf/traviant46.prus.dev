<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Symfony\Component\HttpFoundation\Response;

class ThrottleGameEndpoints
{
    /**
     * Map of limiter names to the request URI patterns they should guard.
     *
     * @var array<string, list<string>>
     */
    private const LIMITERS = [
        'game.market' => ['market', 'market/*'],
        'game.send' => ['send', 'send/*'],
        'game.messages' => ['messages', 'messages/*'],
    ];

    public function __construct(private readonly ThrottleRequests $throttle) {}

    public function handle(Request $request, Closure $next): Response
    {
        foreach (self::LIMITERS as $limiter => $patterns) {
            if ($request->is(...$patterns)) {
                return $this->throttle->handle($request, $next, $limiter);
            }
        }

        return $next($request);
    }
}
