<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Http\SitterSessionContext;
use Closure;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Symfony\Component\HttpFoundation\Response;

class SitterContextMiddleware
{
    public function __construct(
        private readonly SitterSessionContext $sessionContext,
        private readonly ViewFactory $viewFactory,
    ) {}

    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $context = $this->sessionContext->resolve();

        $this->viewFactory->share('sitterContext', $context);
        $request->attributes->set('sitterContext', $context);

        if (! empty($context['active'])) {
            $owner = $context['account'] ?? [];
            $sitter = $context['sitter'] ?? [];

            Context::add(array_filter([
                'acting_as_sitter' => true,
                'acting_owner_id' => $owner['id'] ?? null,
                'acting_owner_username' => $owner['username'] ?? null,
                'acting_sitter_id' => $sitter['id'] ?? null,
                'acting_sitter_username' => $sitter['username'] ?? null,
                'acted_by' => $sitter['username'] ?? null,
            ], static fn ($value) => $value !== null && $value !== ''));

            $request->attributes->set('sitter.acting_as', true);
            $request->attributes->set('sitter.acted_by', array_filter([
                'id' => $sitter['id'] ?? null,
                'username' => $sitter['username'] ?? null,
                'name' => $sitter['name'] ?? null,
            ], static fn ($value) => $value !== null && $value !== ''));
            $request->attributes->set('sitter.owner', array_filter([
                'id' => $owner['id'] ?? null,
                'username' => $owner['username'] ?? null,
                'name' => $owner['name'] ?? null,
            ], static fn ($value) => $value !== null && $value !== ''));
        } else {
            $request->attributes->set('sitter.acting_as', false);
        }

        /** @var Response $response */
        $response = $next($request);

        return $response;
    }
}
