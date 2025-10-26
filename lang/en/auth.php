<?php

declare(strict_types=1);

return [
    'shared' => [
        'fields' => [
            'email' => [
                'label' => 'Email address',
            ],
            'password' => [
                'label' => 'New password',
            ],
            'password_confirmation' => [
                'label' => 'Confirm new password',
            ],
        ],
    ],

    'passwords' => [
        'email' => [
            'title' => 'Reset password',
            'heading' => 'Reset your password',
            'subtitle' => 'We will email a secure link so you can choose a new password.',
            'label' => 'Email address',
            'actions' => [
                'submit' => 'Send password reset link',
            ],
        ],

        'reset' => [
            'title' => 'Choose a new password',
            'heading' => 'Create a new password',
            'subtitle' => 'Enter your email and pick a strong new password to regain access.',
            'actions' => [
                'submit' => 'Update password',
            ],
        ],

        'links' => [
            'login' => 'Back to login',
            'register' => 'Create a new account',
        ],
    ],

    'verification' => [
        'title' => 'Verify your email',
        'heading' => 'Confirm your email address',
        'subtitle' => 'Activate account alerts by confirming your email.',
        'instructions' => 'We sent you a verification link. Click it to finish setting up your account or request another copy below.',
        'current_email_label' => 'Signed in as',
        'status' => [
            'resent' => 'A new verification link has been sent to your email address.',
        ],
        'actions' => [
            'resend' => 'Resend verification email',
            'logout' => 'Log out',
        ],
        'support_prompt' => 'Need to update your contact address?',
        'support_cta' => 'Contact support',
    ],
];
