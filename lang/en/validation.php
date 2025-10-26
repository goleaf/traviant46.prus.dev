<?php

declare(strict_types=1);

$defaultValidation = require base_path('vendor/laravel/framework/src/Illuminate/Translation/lang/en/validation.php');

return array_replace_recursive($defaultValidation, [
    'custom' => [
        'email' => [
            'required' => 'Please enter the email address associated with your account.',
            'email' => 'Enter a valid email address.',
            'exists' => 'We could not find an account with that email address.',
        ],
        'password' => [
            'required' => 'Please choose a password.',
            'confirmed' => 'Make sure both password fields match.',
            'min' => [
                'string' => 'Passwords must be at least :min characters.',
            ],
        ],
    ],
    'attributes' => [
        'email' => 'email address',
        'password' => 'password',
        'password_confirmation' => 'password confirmation',
        'token' => 'reset token',
    ],
]);
