<?php

declare(strict_types=1);

namespace App\Services\Security;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class DeviceFingerprintService
{
    /**
     * Build a deterministic hash representing the current device.
     */
    public function hash(Request $request): string
    {
        $pieces = [
            $request->header('x-device-id'),
            $request->header('user-agent', $request->userAgent()),
            $request->header('accept-language'),
            $request->header('sec-ch-ua'),
            $request->header('sec-ch-ua-platform'),
            $request->header('sec-ch-ua-mobile'),
            $request->header('dnt'),
            $request->header('x-forwarded-for'),
        ];

        $normalized = implode('|', array_map(static function ($value): string {
            return trim((string) $value);
        }, array_filter($pieces, static fn ($value): bool => filled($value))));

        if ($normalized === '') {
            return hash('sha256', $request->ip() ?? Str::uuid()->toString());
        }

        return hash('sha256', $normalized);
    }

    /**
     * Capture additional context for explainability and auditing.
     *
     * @return array<string, mixed>
     */
    public function snapshot(Request $request): array
    {
        $headers = [
            'user_agent' => $request->userAgent(),
            'accept_language' => $request->header('accept-language'),
            'sec_ch_ua' => $request->header('sec-ch-ua'),
            'sec_ch_ua_platform' => $request->header('sec-ch-ua-platform'),
            'sec_ch_ua_mobile' => $request->header('sec-ch-ua-mobile'),
            'dnt' => $request->header('dnt'),
            'timezone' => $request->header('x-timezone'),
            'client_hints' => $request->header('client-hints'),
        ];

        return Arr::where($headers, static fn ($value): bool => filled($value));
    }
}
