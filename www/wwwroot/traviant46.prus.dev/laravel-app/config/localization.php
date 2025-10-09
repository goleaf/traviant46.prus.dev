<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Supported Application Locales
    |--------------------------------------------------------------------------
    |
    | This array lists the locales that are available to end users when
    | interacting with translatable models or localization aware features.
    | It is kept in a dedicated config file so additional localization
    | related options can be collected in one place.
    |
    */
    'supported_locales' => array_values(array_filter(array_map(
        static fn ($locale) => trim($locale),
        explode(',', env('APP_SUPPORTED_LOCALES', 'en'))
    ))),

    /*
    |--------------------------------------------------------------------------
    | Default Locale Fallback
    |--------------------------------------------------------------------------
    |
    | The fallback locale is used whenever a translation does not exist for
    | the active locale. This value typically mirrors the application's
    | fallback locale to keep things consistent across packages.
    |
    */
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),
];
