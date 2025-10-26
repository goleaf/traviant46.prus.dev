<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     version="1.0.0",
 *    title="Travian T46 API",
 *    description="Versioned API for managing sitter assignments and related resources."
 * )
 * @OA\Server(
 *     url="/api/v1",
 *     description="Primary application server"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="cookieAuth",
 *     type="apiKey",
 *     in="cookie",
 *     name="laravel_session",
 *     description="Leverages the Laravel session cookie for authentication. Ensure a valid login before calling these endpoints."
 * )
 */
final class OpenApi {}
