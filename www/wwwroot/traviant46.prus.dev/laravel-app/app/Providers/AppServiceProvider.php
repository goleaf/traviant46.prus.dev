<?php

namespace App\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Spatie\Translatable\Translatable;
use function array_filter;
use function array_values;
use function config;

class AppServiceProvider extends ServiceProvider
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
    public function boot(): void
    {
        $supportedLocales = array_values(array_filter(
            config('localization.supported_locales', [])
        ));

        if ($supportedLocales === []) {
            $supportedLocales = [config('app.locale')];
        }

        Config::set('app.supported_locales', $supportedLocales);

        app(Translatable::class)->fallback(
            config('localization.fallback_locale'),
            false
        );
    }
}
