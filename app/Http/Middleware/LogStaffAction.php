<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\StaffRole;
use App\Models\User;
use App\Services\Security\AuditLogger;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;

class LogStaffAction
{
    /**
     * @param array<int, string> $sensitiveKeys
     */
    public function __construct(
        protected AuditLogger $logger,
        protected array $sensitiveKeys = [
            'password',
            'password_confirmation',
            'current_password',
            'token',
            '_token',
        ],
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $user = $request->user();
        if (! $user instanceof User) {
            return $response;
        }

        if (! $this->shouldAudit($user, $request)) {
            return $response;
        }

        $metadata = [
            'route' => $request->route()?->getName(),
            'method' => $request->getMethod(),
            'path' => '/'.$request->path(),
            'status' => $response->getStatusCode(),
            'payload' => $this->sanitizePayload($request->all()),
        ];

        $actedBy = $request->attributes->get('sitter.acted_by');
        if (is_array($actedBy) && $actedBy !== []) {
            $metadata['acted_by'] = $actedBy;

            if (! array_key_exists('acting_on', $metadata)) {
                $ownerContext = $request->attributes->get('sitter.owner');
                $ownerMetadata = is_array($ownerContext) ? array_filter($ownerContext, static fn ($value) => $value !== null && $value !== '') : [];

                if ($ownerMetadata === []) {
                    $ownerMetadata = array_filter([
                        'id' => $user->getKey(),
                        'username' => $user->username,
                        'name' => $user->name,
                    ], static fn ($value) => $value !== null && $value !== '');
                }

                if ($ownerMetadata !== []) {
                    $metadata['acting_on'] = $ownerMetadata;
                }
            }
        }

        $this->logger->log(
            $user,
            action: sprintf('http.%s', strtolower($request->getMethod())),
            metadata: $metadata,
            target: null,
            ipAddress: $request->ip(),
        );

        return $response;
    }

    protected function shouldAudit(User $user, Request $request): bool
    {
        if ($request->isMethodSafe()) {
            return false;
        }

        if ($user->isMultihunter()) {
            return true;
        }

        return $user->staffRole() !== StaffRole::Player;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    protected function sanitizePayload(array $payload): array
    {
        $clean = Arr::except($payload, $this->sensitiveKeys);
        $clean = array_slice($clean, 0, 25, true);

        return array_map(static function ($value) {
            if (is_array($value)) {
                return '[array]';
            }

            if (is_string($value) && strlen($value) > 255) {
                return substr($value, 0, 252).'...';
            }

            return $value;
        }, $clean);
    }
}
