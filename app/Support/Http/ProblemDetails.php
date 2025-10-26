<?php

declare(strict_types=1);

namespace App\Support\Http;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * @OA\Schema(
 *     schema="ProblemDetails",
 *     type="object",
 *     required={"type","title","status","detail","code"},
 *     @OA\Property(property="type", type="string", example="https://prus.dev/problems/validation-failed"),
 *     @OA\Property(property="title", type="string", example="Validation failed"),
 *     @OA\Property(property="status", type="integer", example=422),
 *     @OA\Property(property="detail", type="string", example="Validation failed. Review the `errors` object for fields that need attention."),
 *     @OA\Property(property="instance", type="string", nullable=true, example="/api/v1/sitters"),
 *     @OA\Property(property="code", type="string", example="validation_failed"),
 *     @OA\Property(property="errors", type="object", nullable=true)
 * )
 */
final class ProblemDetails
{
    /**
     * @param array<string, mixed> $extensions
     */
    private function __construct(
        private readonly string $type,
        private readonly string $title,
        private readonly string $detail,
        private readonly string $code,
        private readonly int $status,
        private readonly ?string $instance,
        private readonly array $extensions,
        private readonly array $headers
    ) {}

    public static function fromException(Throwable $throwable, Request $request): self
    {
        if ($throwable instanceof HttpResponseException) {
            $response = $throwable->getResponse();

            if ($response instanceof JsonResponse) {
                $status = $response->getStatusCode();
                $data = json_decode($response->getContent() ?: '[]', true) ?: [];

                return new self(
                    type: self::typeForCode($data['code'] ?? 'http-response-error'),
                    title: $data['title'] ?? Response::$statusTexts[$status] ?? 'HTTP Response Error',
                    detail: $data['detail'] ?? 'The request could not be completed.',
                    code: $data['code'] ?? 'http-response-error',
                    status: $status,
                    instance: self::instance($request),
                    extensions: array_diff_key($data, array_flip(['type', 'title', 'detail', 'status', 'instance', 'code'])),
                    headers: $response->headers->all(),
                );
            }

            return self::fromException($throwable->getPrevious() ?? new NotFoundHttpException, $request);
        }

        if ($throwable instanceof ValidationException) {
            return new self(
                type: self::typeForCode('validation_failed'),
                title: 'Validation failed',
                detail: 'Validation failed. Review the `errors` object for fields that need attention.',
                code: 'validation_failed',
                status: $throwable->status,
                instance: self::instance($request),
                extensions: [
                    'errors' => $throwable->errors(),
                ],
                headers: [],
            );
        }

        if ($throwable instanceof AuthenticationException) {
            return new self(
                type: self::typeForCode('unauthenticated'),
                title: 'Unauthenticated',
                detail: 'You must be authenticated to access this resource.',
                code: 'unauthenticated',
                status: 401,
                instance: self::instance($request),
                extensions: [],
                headers: [],
            );
        }

        if ($throwable instanceof AuthorizationException) {
            return new self(
                type: self::typeForCode('forbidden'),
                title: 'Forbidden',
                detail: 'You are not allowed to perform this action with the current account.',
                code: 'forbidden',
                status: 403,
                instance: self::instance($request),
                extensions: [],
                headers: [],
            );
        }

        if ($throwable instanceof ModelNotFoundException) {
            return new self(
                type: self::typeForCode('resource_not_found'),
                title: 'Resource not found',
                detail: 'The requested resource was not found.',
                code: 'resource_not_found',
                status: 404,
                instance: self::instance($request),
                extensions: [],
                headers: [],
            );
        }

        if ($throwable instanceof ThrottleRequestsException) {
            $headers = $throwable->getHeaders();

            $retryAfter = $headers['Retry-After'] ?? $headers['retry-after'] ?? null;
            $limit = $headers['X-RateLimit-Limit'] ?? $headers['x-ratelimit-limit'] ?? null;
            $remaining = $headers['X-RateLimit-Remaining'] ?? $headers['x-ratelimit-remaining'] ?? null;

            $meta = array_filter([
                'retry_after' => is_numeric($retryAfter) ? (int) $retryAfter : $retryAfter,
                'limit' => is_numeric($limit) ? (int) $limit : $limit,
                'remaining' => is_numeric($remaining) ? (int) $remaining : $remaining,
            ], static fn ($value) => $value !== null);

            return new self(
                type: self::typeForCode('too_many_requests'),
                title: 'Too Many Requests',
                detail: 'Too many requests were made in a short period. Wait for the retry window before trying again.',
                code: 'too_many_requests',
                status: $throwable->getStatusCode(),
                instance: self::instance($request),
                extensions: empty($meta) ? [] : ['meta' => $meta],
                headers: $headers,
            );
        }

        if ($throwable instanceof HttpExceptionInterface) {
            $status = $throwable->getStatusCode();
            $code = self::codeForHttpStatus($status);

            return new self(
                type: self::typeForCode($code),
                title: Response::$statusTexts[$status] ?? 'HTTP Error',
                detail: self::detailForHttpStatus($status, $throwable->getMessage()),
                code: $code,
                status: $status,
                instance: self::instance($request),
                extensions: [],
                headers: $throwable->getHeaders(),
            );
        }

        return new self(
            type: self::typeForCode('server_error'),
            title: 'Internal server error',
            detail: 'An unexpected error occurred. Please try again later or contact support if the issue persists.',
            code: 'server_error',
            status: 500,
            instance: self::instance($request),
            extensions: [],
            headers: [],
        );
    }

    public function toResponse(): JsonResponse
    {
        $payload = [
            'type' => $this->type,
            'title' => $this->title,
            'status' => $this->status,
            'detail' => $this->detail,
            'instance' => $this->instance,
            'code' => $this->code,
        ] + $this->extensions;

        return response()->json($payload, $this->status)
            ->setEncodingOptions(JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            ->header('Content-Type', 'application/problem+json')
            ->withHeaders($this->headers);
    }

    private static function typeForCode(string $code): string
    {
        return sprintf('https://prus.dev/problems/%s', str_replace('_', '-', $code));
    }

    private static function codeForHttpStatus(int $status): string
    {
        return match ($status) {
            400 => 'bad_request',
            401 => 'unauthenticated',
            403 => 'forbidden',
            404 => 'resource_not_found',
            405 => 'method_not_allowed',
            409 => 'conflict',
            410 => 'gone',
            415 => 'unsupported_media_type',
            422 => 'unprocessable_content',
            429 => 'too_many_requests',
            default => $status >= 500 ? 'server_error' : 'http_error',
        };
    }

    private static function detailForHttpStatus(int $status, string $fallback): string
    {
        return match ($status) {
            400 => 'The request is malformed. Please verify the payload and try again.',
            401 => 'You must be authenticated to access this resource.',
            403 => 'You are not allowed to perform this action with the current account.',
            404 => 'The requested resource was not found.',
            405 => 'The HTTP method is not allowed for this endpoint.',
            409 => 'A conflict occurred with the current state of the target resource.',
            410 => 'The requested resource is no longer available.',
            415 => 'The provided media type is not supported.',
            422 => $fallback !== '' ? $fallback : 'The request could not be processed. Please review the submitted data.',
            429 => 'Too many requests were made in a short period. Wait before retrying.',
            default => $fallback !== '' ? $fallback : 'The request could not be completed.',
        };
    }

    private static function instance(Request $request): string
    {
        $path = '/'.ltrim($request->path(), '/');

        return $path === '//' ? '/' : $path;
    }
}
