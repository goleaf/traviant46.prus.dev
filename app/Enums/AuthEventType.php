<?php

declare(strict_types=1);

namespace App\Enums;

enum AuthEventType: string
{
    case PasswordResetRequested = 'password_reset_requested';
    case PasswordResetCompleted = 'password_reset_completed';
    case VerificationEmailSent = 'verification_email_sent';
    case EmailVerified = 'email_verified';
}
