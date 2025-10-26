<?php

declare(strict_types=1);

$rootPath = dirname(__DIR__, 2);

it('ensures the login form associates labels and errors', function () use ($rootPath) {
    $contents = file_get_contents($rootPath.'/resources/views/auth/login.blade.php');

    expect($contents)
        ->toContain("<label for=\"login\">{{ __('auth.login.fields.login.label') }}</label>")
        ->toContain('id="{{ $loginErrorId }}" class="legacy-error" role="alert" aria-live="assertive"');
});

it('ensures the registration form exposes accessible attributes', function () use ($rootPath) {
    $contents = file_get_contents($rootPath.'/resources/views/auth/register.blade.php');

    expect($contents)
        ->toContain("<label for=\"username\">{{ __('auth.register.fields.username.label') }}</label>")
        ->toContain('aria-describedby="{{ $passwordErrorId }}"')
        ->toContain('id="{{ $passwordErrorId }}" class="legacy-error" role="alert" aria-live="assertive"');
});

it('ensures the segment form wires hints and errors correctly', function () use ($rootPath) {
    $contents = file_get_contents($rootPath.'/resources/views/admin/campaign-customer-segments/_form.blade.php');

    expect($contents)
        ->toContain('aria-describedby="{{ $nameDescribedBy }}"')
        ->toContain('id="{{ $nameErrorId }}" class="mt-1 text-sm text-red-600" role="alert" aria-live="polite"');
});
