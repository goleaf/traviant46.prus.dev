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
            'title' => 'Request a TravianT reset link',
            'heading' => 'Queue a reset for your commander profile',
            'subtitle' => 'Enter the email tied to your TravianT account and we will dispatch a secure recovery link through our mail queue.',
            'label' => 'Account email',
            'actions' => [
                'submit' => 'Send reset link to my inbox',
            ],
        ],

        'reset' => [
            'title' => 'Forge a fresh password',
            'heading' => 'Lock down your TravianT empire',
            'subtitle' => 'Confirm your email and craft a strong password to reclaim command of your villages.',
            'actions' => [
                'submit' => 'Update password and return to TravianT',
            ],
        ],

        'links' => [
            'login' => 'Return to the login gate',
            'register' => 'Recruit a new account',
        ],
    ],

    'verification' => [
        'title' => 'Verify your TravianT contact address',
        'heading' => 'Confirm your rally horn',
        'subtitle' => 'Verification proves you own the inbox we use for siege alerts, sitter approvals, and premium receipts.',
        'instructions' => 'We just queued a verification link. Open your inbox, follow the button to confirm, or request another dispatch below.',
        'current_email_label' => 'Signed in as',
        'status' => [
            'resent' => 'A fresh verification link is on its way to your inbox.',
        ],
        'actions' => [
            'resend' => 'Send another verification email',
            'logout' => 'Log out',
        ],
        'support_prompt' => 'Need to switch to a different war room address?',
        'support_cta' => 'Contact support',
    ],
];
