<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Models\TrustedDevice;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Cookie\QueueingFactory as CookieJar;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie;

class TrustedDeviceManager
{
    public function __construct(
        protected CookieJar $cookieJar,
    ) {}

    public function cookieName(): string
    {
        return Config::get('security.trusted_devices.cookie.name', 'travian_trusted_device');
    }

    public function isEnabled(): bool
    {
        return (bool) Config::get('security.trusted_devices.enabled', true);
    }

    public function resolveCurrentDevice(User $user, Request $request, bool $refreshLastUsed = true): ?TrustedDevice
    {
        if (! $this->isEnabled()) {
            return null;
        }

        $cookieValue = (string) $request->cookies->get($this->cookieName(), '');

        if ($cookieValue === '') {
            return null;
        }

        [$publicId, $token] = $this->parseCookiePayload($cookieValue);

        if ($publicId === null || $token === null) {
            return null;
        }

        $device = TrustedDevice::query()
            ->forUser($user)
            ->where('public_id', $publicId)
            ->matchingToken($this->hashToken($token))
            ->first();

        if (! $device instanceof TrustedDevice || ! $device->isActive()) {
            return null;
        }

        if ($refreshLastUsed) {
            $this->touch($device, $request);
        }

        return $device;
    }

    public function rememberCurrentDevice(User $user, Request $request, ?string $label = null, ?Carbon $expiresAt = null): TrustedDevice
    {
        if (! $this->isEnabled()) {
            throw new AuthorizationException(__('Trusted devices are disabled for this environment.'));
        }

        [$token, $tokenHash] = $this->issueTokenPair();
        $fingerprint = $this->fingerprintHash($request);
        $now = Date::now();

        if ($expiresAt === null) {
            $expiryDays = (int) Config::get('security.trusted_devices.default_expiration_days', 180);

            if ($expiryDays > 0) {
                $expiresAt = $now->copy()->addDays($expiryDays);
            }
        }

        /** @var TrustedDevice|null $device */
        $device = TrustedDevice::query()
            ->forUser($user)
            ->matchingFingerprint($fingerprint)
            ->whereNull('revoked_at')
            ->orderByDesc('first_trusted_at')
            ->first();

        $payload = [
            'label' => $label !== null ? trim($label) : null,
            'token_hash' => $tokenHash,
            'fingerprint_hash' => $fingerprint,
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'metadata' => $this->extractMetadata($request),
            'last_used_at' => $now,
            'expires_at' => $expiresAt,
            'revoked_at' => null,
        ];

        if ($device instanceof TrustedDevice) {
            $device->fill($payload);
        } else {
            $device = $user->trustedDevices()->make(array_merge($payload, [
                'public_id' => (string) Str::uuid(),
                'first_trusted_at' => $now,
            ]));
        }

        $device->save();

        $this->queueCookie($device, $token, $expiresAt);
        $this->pruneExcessDevices($user);

        return $device;
    }

    public function revokeDevice(User $user, string $publicId): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        $updated = TrustedDevice::query()
            ->forUser($user)
            ->where('public_id', $publicId)
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => Date::now(),
            ]);

        if ($updated > 0) {
            $current = $this->currentCookiePublicId($user);

            if ($current !== null && $current === $publicId) {
                $this->forgetCookie();
            }
        }

        return (bool) $updated;
    }

    public function revokeAll(User $user, ?array $exceptPublicIds = null): int
    {
        if (! $this->isEnabled()) {
            return 0;
        }

        $query = TrustedDevice::query()
            ->forUser($user)
            ->whereNull('revoked_at');

        if ($exceptPublicIds !== null && $exceptPublicIds !== []) {
            $query->whereNotIn('public_id', $exceptPublicIds);
        }

        $count = $query->update([
            'revoked_at' => Date::now(),
        ]);

        $current = $this->currentCookiePublicId($user);

        if ($current !== null && ($exceptPublicIds === null || ! in_array($current, $exceptPublicIds, true))) {
            $this->forgetCookie();
        }

        return $count;
    }

    public function renameDevice(User $user, string $publicId, string $name): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        return (bool) TrustedDevice::query()
            ->forUser($user)
            ->where('public_id', $publicId)
            ->update([
                'label' => trim($name),
                'updated_at' => Date::now(),
            ]);
    }

    /**
     * @return array{
     *     devices: \Illuminate\Support\Collection<int, TrustedDevice>,
     *     current: ?TrustedDevice
     * }
     */
    public function listDevices(User $user, Request $request): array
    {
        $devices = TrustedDevice::query()
            ->forUser($user)
            ->orderByDesc('last_used_at')
            ->orderByDesc('first_trusted_at')
            ->get();

        $current = $this->resolveCurrentDevice($user, $request, false);

        return [
            'devices' => $devices,
            'current' => $current,
        ];
    }

    public function touch(TrustedDevice $device, Request $request): void
    {
        $device->fill([
            'last_used_at' => Date::now(),
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ])->save();
    }

    public function forgetCookie(): void
    {
        $this->cookieJar->queue($this->cookieJar->forget($this->cookieName()));
    }

    protected function extractMetadata(Request $request): array
    {
        return array_filter([
            'accept_language' => $request->header('accept-language'),
            'sec_ch_ua' => $request->header('sec-ch-ua'),
            'sec_ch_ua_platform' => $request->header('sec-ch-ua-platform'),
            'sec_ch_ua_mobile' => $request->header('sec-ch-ua-mobile'),
        ], static fn ($value) => $value !== null);
    }

    protected function fingerprintHash(Request $request): ?string
    {
        $userAgent = (string) $request->userAgent();

        if ($userAgent === '') {
            return null;
        }

        $ip = (string) $request->ip();
        $ipPrefix = $ip !== '' ? implode('.', array_slice(explode('.', $ip), 0, 3)) : '';

        return hash('sha256', $userAgent.'|'.$ipPrefix);
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function issueTokenPair(): array
    {
        $plain = bin2hex(random_bytes(32));

        return [$plain, $this->hashToken($plain)];
    }

    protected function hashToken(string $token): string
    {
        return hash_hmac(
            'sha512',
            $token,
            config('app.key', 'trusted-device-secret'),
        );
    }

    protected function queueCookie(TrustedDevice $device, string $token, ?Carbon $expiresAt = null): void
    {
        $lifetimeDays = (int) Config::get('security.trusted_devices.cookie.lifetime_days', 180);

        $expires = $expiresAt ?? Date::now()->addDays(max($lifetimeDays, 1));

        $this->cookieJar->queue(
            new Cookie(
                name: $this->cookieName(),
                value: $this->buildCookieValue($device->public_id, $token),
                expire: $expires,
                path: '/',
                domain: Config::get('session.domain'),
                secure: Config::get('session.secure', true),
                httpOnly: true,
                raw: false,
                sameSite: Config::get('security.trusted_devices.cookie.same_site', Config::get('session.same_site', 'lax')),
            ),
        );
    }

    protected function buildCookieValue(string $publicId, string $token): string
    {
        return base64_encode(json_encode([
            'id' => $publicId,
            'token' => $token,
        ], JSON_THROW_ON_ERROR));
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    protected function parseCookiePayload(string $payload): array
    {
        try {
            $decoded = json_decode(base64_decode($payload, true) ?: '', true, flags: JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return [null, null];
        }

        $publicId = is_string($decoded['id'] ?? null) ? $decoded['id'] : null;
        $token = is_string($decoded['token'] ?? null) ? $decoded['token'] : null;

        return [$publicId, $token];
    }

    protected function pruneExcessDevices(User $user): void
    {
        $maximum = (int) Config::get('security.trusted_devices.max_per_user', 10);

        if ($maximum <= 0) {
            return;
        }

        $deviceIds = TrustedDevice::query()
            ->forUser($user)
            ->whereNull('revoked_at')
            ->orderByDesc('last_used_at')
            ->orderByDesc('first_trusted_at')
            ->skip($maximum)
            ->limit(PHP_INT_MAX)
            ->pluck('id');

        if ($deviceIds->isNotEmpty()) {
            TrustedDevice::query()
                ->whereIn('id', $deviceIds)
                ->update(['revoked_at' => Date::now()]);
        }
    }

    protected function currentCookiePublicId(User $user): ?string
    {
        $cookieValue = (string) request()->cookies->get($this->cookieName(), '');

        if ($cookieValue === '') {
            return null;
        }

        [$publicId] = $this->parseCookiePayload($cookieValue);

        if ($publicId === null) {
            return null;
        }

        $exists = TrustedDevice::query()
            ->forUser($user)
            ->where('public_id', $publicId)
            ->exists();

        return $exists ? $publicId : null;
    }
}
