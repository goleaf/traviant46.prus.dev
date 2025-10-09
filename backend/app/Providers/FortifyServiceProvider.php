<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Services\Auth\LegacyLoginResult;
use App\Services\Auth\LegacyLoginService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(LegacyLoginService $legacyLoginService): void
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

        Fortify::authenticateUsing(function (Request $request) use ($legacyLoginService) {
            $result = $legacyLoginService->attempt(
                (string) $request->input('login', $request->input('email')), // backwards compatibility
                (string) $request->input('password')
            );

            if (! $result instanceof LegacyLoginResult) {
                return null;
            }

            if (! $result->successful()) {
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

            return $result->user;
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}
