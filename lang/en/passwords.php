<?php

declare(strict_types=1);

$defaultPasswords = require base_path('vendor/laravel/framework/src/Illuminate/Translation/lang/en/passwords.php');

return array_replace_recursive($defaultPasswords, [
    'reset' => 'Your password has been updated.',
    'sent' => 'We sent a password reset link to your email address.',
    'throttled' => 'Please wait before requesting another password reset link.',
    'token' => 'This password reset link is no longer valid.',
    'user' => 'We could not find an account with that email address.',
]);
