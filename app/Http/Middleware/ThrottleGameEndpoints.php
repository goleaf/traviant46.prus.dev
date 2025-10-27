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
     * Guard marketplace access from both direct routes and Livewire payloads.
     *
     * @var list<string>
     */
    private const MARKET_PATTERNS = [
        'market',
        'market/*',
        'villages/*/market',
        'villages/*/market/*',
        'livewire/message/game.market',
    ];

    /**
     * Cover all rally point send flows, including nested rally-point paths.
     *
     * @var list<string>
     */
    private const SEND_PATTERNS = [
        'send',
        'send/*',
        'villages/*/rally-point/send',
        'villages/*/rally-point/send/*',
        'livewire/message/game.send',
    ];

    /**
     * Ensure in-game messaging adheres to throttling on page loads and Livewire updates.
     *
     * @var list<string>
     */
    private const MESSAGE_PATTERNS = [
        'messages',
        'messages/*',
        'livewire/message/game.messages',
    ];

    /**
     * Map of limiter names to the request URI patterns they should guard.
     *
     * @var array<string, list<string>>
     */
    private const LIMITERS = [
        'game.market' => self::MARKET_PATTERNS,
        'game.send' => self::SEND_PATTERNS,
        'game.messages' => self::MESSAGE_PATTERNS,
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
