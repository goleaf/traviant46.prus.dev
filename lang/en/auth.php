<?php

declare(strict_types=1);

return [
    'alerts' => [
        'error_summary_label' => 'Form submission issues',
        'error_summary_heading' => 'Please review the highlighted fields below.',
    ],

    'shared' => [
        'fields' => [
            'email' => [
                'label' => 'Email address',
            ],
            'password' => [
                'label' => 'Password',
            ],
            'password_confirmation' => [
                'label' => 'Confirm password',
            ],
        ],
    ],

    'login' => [
        'fields' => [
            'login' => [
                'label' => 'Account name or Email address',
            ],
            'remember' => [
                'label' => 'Remember me',
                'help' => 'Keep me signed in on this device',
            ],
        ],
        'actions' => [
            'submit' => 'Log in to your empire',
        ],
        'links' => [
            'forgot_password' => 'Forgot your password?',
            'register' => 'Create a new account',
        ],
    ],

    'register' => [
        'fields' => [
            'username' => [
                'label' => 'Account name',
            ],
            'name' => [
                'label' => 'Display name',
            ],
        ],
        'actions' => [
            'submit' => 'Create my Travian account',
        ],
        'links' => [
            'login' => 'Already have an account? Sign in',
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
            'fields' => [
                'password' => [
                    'label' => 'New password',
                ],
                'password_confirmation' => [
                    'label' => 'Confirm new password',
                ],
            ],
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
