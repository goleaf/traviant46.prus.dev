<?php

declare(strict_types=1);

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Http\Controllers\Auth\EmailVerificationNotificationController as AppEmailVerificationNotificationController;
use App\Http\Controllers\Auth\PasswordResetLinkController as AppPasswordResetLinkController;
use App\Monitoring\Metrics\MetricRecorder;
use App\Services\Auth\LegacyLoginResult;
use App\Services\Auth\LegacyLoginService;
use App\Services\Auth\LoginRateLimiter as AppLoginRateLimiter;
use App\Services\Security\DeviceFingerprintService;
use App\Services\Security\IpReputationService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Http\Controllers\EmailVerificationNotificationController;
use Laravel\Fortify\Http\Controllers\PasswordResetLinkController;
use Laravel\Fortify\LoginRateLimiter;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PasswordResetLinkController::class, AppPasswordResetLinkController::class);
        $this->app->bind(EmailVerificationNotificationController::class, AppEmailVerificationNotificationController::class);
        $this->app->singleton(LoginRateLimiter::class, AppLoginRateLimiter::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(LegacyLoginService $legacyLoginService, IpReputationService $ipReputation): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);

        Fortify::loginView(fn () => view('auth.login'));
        Fortify::registerView(fn () => view('auth.register'));
        Fortify::requestPasswordResetLinkView(fn () => view('auth.forgot-password'));
        Fortify::resetPasswordView(fn ($request) => view('auth.reset-password', ['request' => $request]));
        Fortify::verifyEmailView(fn () => view('auth.verify-email'));

        Fortify::redirects('login', '/home');
        Fortify::redirects('register', '/home');
        Fortify::redirects('logout', '/login');

        Fortify::authenticateUsing(function (Request $request) use ($legacyLoginService, $ipReputation) {
            /** @var MetricRecorder $metrics */
            $metrics = app(MetricRecorder::class);

            $ipAddress = (string) $request->ip();

            if ($ipAddress !== '') {
                $reputation = $ipReputation->evaluate($ipAddress);
                $request->attributes->set('security.ip_reputation', $reputation);

                if ($ipReputation->shouldBlock($reputation)) {
                    Log::warning('auth.login.blocked_ip', array_merge($reputation->toArray(), [
                        'identifier' => $request->input('login') ?? $request->input('email'),
                    ]));

                    throw ValidationException::withMessages([
                        Fortify::username() => [__('auth.blocked_ip')],
                    ]);
                }
            }

            $identifier = collect([
                Fortify::username(),
                'login',
                'email',
                'username',
            ])->map(fn (string $field) => $request->input($field))
                ->filter(fn ($value) => is_string($value) && trim($value) !== '')
                ->first();

            $identifier = is_string($identifier) ? trim($identifier) : '';

            if ($identifier !== '') {
                $request->merge([Fortify::username() => $identifier]);
            }

            $result = $legacyLoginService->attempt(
                $identifier,
                (string) $request->input('password'),
            );

            if (! $result instanceof LegacyLoginResult) {
                return null;
            }

            if (! $result->successful()) {
                $metrics->increment('auth.logins', 1.0, [
                    'status' => 'failed',
                    'guard' => 'web',
                    'failure' => $result->mode,
                ]);

                if ($result->mode === LegacyLoginResult::MODE_ACTIVATION) {
                    throw ValidationException::withMessages([
                        Fortify::username() => __('Your account is pending activation. Please verify your email before logging in.'),
                    ]);
                }

                return null;
            }

            if ($result->viaSitter()) {
                $request->session()->put('auth.acting_as_sitter', true);
                $request->session()->put('auth.sitter_id', optional($result->sitter)->getKey());
            } else {
                $request->session()->forget('auth.acting_as_sitter');
                $request->session()->forget('auth.sitter_id');
            }

            if ($request->boolean('remember_device')) {
                $request->session()->put('auth.remember_device', true);
            } else {
                $request->session()->forget('auth.remember_device');
            }

            return $result->user;
        });

        $loginRateLimit = config('security.rate_limits.login', []);
        $twoFactorRateLimit = config('security.rate_limits.two_factor', []);
        $passwordResetRateLimit = config('fortify.rate_limits.password_reset', []);
        $verificationRateLimit = config('fortify.rate_limits.verification', []);

        RateLimiter::for('login', function (Request $request) use ($loginRateLimit) {
            $maxAttempts = max(1, (int) ($loginRateLimit['max_attempts'] ?? 5));
            $decayMinutes = max(1, (int) ($loginRateLimit['decay_minutes'] ?? 1));

            $identifierField = Fortify::username();
            $identifier = (string) $request->input($identifierField, '');

            if ($identifier === '' && $request->filled('email')) {
                $identifier = (string) $request->input('email');
            }

            $normalizedIdentifier = Str::transliterate(Str::lower($identifier) ?: 'guest');
            $ipAddress = (string) $request->ip();

            $limits = [
                Limit::perMinutes($decayMinutes, $maxAttempts)
                    ->by($normalizedIdentifier.'|'.$ipAddress)
                    ->response(function () use ($normalizedIdentifier, $ipAddress, $decayMinutes) {
                        $lockoutKey = md5('login'.$normalizedIdentifier.'|'.$ipAddress);
                        $seconds = (int) RateLimiter::availableIn($lockoutKey);

                        if ($seconds <= 0) {
                            $seconds = $decayMinutes * 60;
                        }

                        throw ValidationException::withMessages([
                            Fortify::username() => __('auth.throttle', [
                                'seconds' => $seconds,
                                'minutes' => (int) ceil($seconds / 60),
                            ]),
                        ])->status(429);
                    }),
            ];

            $ipConfig = $loginRateLimit['per_ip'] ?? null;

            if (is_array($ipConfig)) {
                $ipMaxAttempts = (int) ($ipConfig['max_attempts'] ?? 0);

                if ($ipMaxAttempts > 0) {
                    $ipDecayMinutes = max(1, (int) ($ipConfig['decay_minutes'] ?? $decayMinutes));
                    $limits[] = Limit::perMinutes($ipDecayMinutes, $ipMaxAttempts)
                        ->by('ip:'.$ipAddress)
                        ->response(function () use ($ipAddress, $ipDecayMinutes) {
                            $lockoutKey = md5('login'.'ip:'.$ipAddress);
                            $seconds = (int) RateLimiter::availableIn($lockoutKey);

                            if ($seconds <= 0) {
                                $seconds = $ipDecayMinutes * 60;
                            }

                            throw ValidationException::withMessages([
                                Fortify::username() => __('auth.throttle', [
                                    'seconds' => $seconds,
                                    'minutes' => (int) ceil($seconds / 60),
                                ]),
                            ])->status(429);
                        });
                }
            }

            $deviceConfig = $loginRateLimit['per_device'] ?? null;

            if (is_array($deviceConfig)) {
                $deviceMaxAttempts = (int) ($deviceConfig['max_attempts'] ?? 0);

                if ($deviceMaxAttempts > 0) {
                    $deviceDecayMinutes = max(1, (int) ($deviceConfig['decay_minutes'] ?? $decayMinutes));
                    $fingerprint = app(DeviceFingerprintService::class)->hash($request);

                    $limits[] = Limit::perMinutes($deviceDecayMinutes, $deviceMaxAttempts)
                        ->by('device:'.$fingerprint)
                        ->response(function () use ($fingerprint, $deviceDecayMinutes, $decayMinutes) {
                            $lockoutKey = md5('login'.'device:'.$fingerprint);
                            $seconds = (int) RateLimiter::availableIn($lockoutKey);

                            if ($seconds <= 0) {
                                $seconds = max($deviceDecayMinutes, $decayMinutes) * 60;
                            }

                            throw ValidationException::withMessages([
                                Fortify::username() => __('auth.throttle', [
                                    'seconds' => $seconds,
                                    'minutes' => (int) ceil($seconds / 60),
                                ]),
                            ])->status(429);
                        });
                }
            }

            return $limits;
        });

        RateLimiter::for('two-factor', function (Request $request) use ($twoFactorRateLimit) {
            $maxAttempts = max(1, (int) ($twoFactorRateLimit['max_attempts'] ?? 5));
            $decayMinutes = max(1, (int) ($twoFactorRateLimit['decay_minutes'] ?? 1));

            return Limit::perMinutes($decayMinutes, $maxAttempts)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('password-reset', function (Request $request) use ($passwordResetRateLimit) {
            $maxAttempts = max(1, (int) ($passwordResetRateLimit['max_attempts'] ?? 5));
            $decaySeconds = max(60, (int) ($passwordResetRateLimit['decay_seconds'] ?? 900));
            $decayMinutes = max(1, (int) ceil($decaySeconds / 60));

            $email = Str::lower((string) $request->input(Fortify::email(), 'anonymous'));
            $ipAddress = (string) $request->ip();

            return Limit::perMinutes($decayMinutes, $maxAttempts)->by($email.'|'.$ipAddress);
        });

        RateLimiter::for('verify-email', function (Request $request) use ($verificationRateLimit) {
            $maxAttempts = max(1, (int) ($verificationRateLimit['max_attempts'] ?? 6));
            $decayMinutes = max(1, (int) ($verificationRateLimit['decay_minutes'] ?? 10));

            $userId = optional($request->user())->getKey();

            return Limit::perMinutes($decayMinutes, $maxAttempts)->by($userId !== null ? 'user:'.$userId : 'ip:'.$request->ip());
        });
    }
}
