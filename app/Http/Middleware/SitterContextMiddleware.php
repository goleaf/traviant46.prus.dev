<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Http\SitterSessionContext;
use Closure;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware that enriches every request with sitter context information.
 */
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
            $owner = $this->normalizeActor($context['account'] ?? []);
            $sitter = $this->normalizeActor($context['sitter'] ?? []);

            // Propagate sitter metadata to the global request context for structured logging.
            Context::add(array_filter([
                'acting_as_sitter' => true,
                'acting_owner_id' => $owner['id'] ?? null,
                'acting_owner_username' => $owner['username'] ?? null,
                'acting_sitter_id' => $sitter['id'] ?? null,
                'acting_sitter_username' => $sitter['username'] ?? null,
                'acted_by' => $sitter !== [] ? $sitter : null,
            ], static fn ($value) => $value !== null && $value !== ''));

            $request->attributes->set('sitter.acting_as', true);
            $request->attributes->set('sitter.acted_by', $sitter);
            $request->attributes->set('sitter.owner', $owner);
        } else {
            // Clear contextual sitter metadata when acting on behalf of the owner directly.
            $request->attributes->set('sitter.acting_as', false);
            $request->attributes->set('sitter.acted_by', []);
            $request->attributes->set('sitter.owner', []);
        }

        /** @var Response $response */
        $response = $next($request);

        return $response;
    }

    /**
     * Normalise actor arrays by removing null or empty scalar values.
     *
     * @param array<string, mixed> $actor
     * @return array<string, mixed>
     */
    private function normalizeActor(array $actor): array
    {
        return array_filter($actor, static fn ($value) => $value !== null && $value !== '');
    }
}
