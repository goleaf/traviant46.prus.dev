<?php

declare(strict_types=1);

namespace App\Support\Http;

use Illuminate\Http\JsonResponse;

final class ThrottleResponse
{
    /**
     * Build a standardised JSON payload for throttled responses.
     */
    public static function json(string $message, int $seconds, int $limit, array $meta = []): JsonResponse
    {
        $seconds = max(1, $seconds);

        $payload = [
            'status' => 'error',
            'code' => 429,
            'message' => $message,
            'meta' => array_merge([
                'wait_seconds' => $seconds,
                'retry_after' => $seconds,
                'limit' => $limit,
            ], $meta),
        ];

        return response()
            ->json($payload, 429)
            ->withHeaders([
                'Retry-After' => (string) $seconds,
            ]);
    }
}
