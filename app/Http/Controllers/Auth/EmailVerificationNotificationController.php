<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\AuthEventType;
use App\Models\AuthEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\EmailVerificationNotificationSentResponse;
use Laravel\Fortify\Http\Controllers\EmailVerificationNotificationController as BaseEmailVerificationNotificationController;
use Laravel\Fortify\Http\Responses\RedirectAsIntended;

class EmailVerificationNotificationController extends BaseEmailVerificationNotificationController
{
    public function store(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return $request->wantsJson()
                ? new JsonResponse('', 204)
                : app(RedirectAsIntended::class, ['name' => 'email-verification']);
        }

        $request->user()->sendEmailVerificationNotification();

        $this->recordAuditEvent($request);

        return app(EmailVerificationNotificationSentResponse::class);
    }

    protected function recordAuditEvent(Request $request): void
    {
        $user = $request->user();

        AuthEvent::query()->create([
            'user_id' => $user?->getKey(),
            'event_type' => AuthEventType::VerificationEmailSent,
            'identifier' => $user?->email,
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'meta' => [
                'resend' => true,
            ],
            'occurred_at' => now(),
        ]);
    }
}
