<?php

declare(strict_types=1);

use App\Enums\AuthEventType;
use App\Models\AuthEvent;
use App\Models\User;
use App\Notifications\QueuedVerifyEmailNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;

uses(RefreshDatabase::class);

it('sends a verification notification to unverified users', function (): void {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    actingAs($user);

    $response = post(route('verification.send'));

    $response->assertRedirect();
    Notification::assertSentTo($user, QueuedVerifyEmailNotification::class, function (object $notification) {
        return $notification instanceof ShouldQueue;
    });

    $auditEvent = AuthEvent::query()->latest('id')->first();

    expect($auditEvent)->not->toBeNull();
    expect($auditEvent?->event_type)->toBe(AuthEventType::VerificationEmailSent);
    expect($auditEvent?->user_id)->toBe($user->id);
});

it('throttles verification resend requests after configured attempts', function (): void {
    $user = User::factory()->unverified()->create();

    actingAs($user);

    $maxAttempts = max(1, (int) (config('fortify.rate_limits.verification.max_attempts') ?? 6));
    $key = 'user:'.$user->id;

    RateLimiter::clear($key);

    foreach (range(1, $maxAttempts) as $attempt) {
        post(route('verification.send'))->assertRedirect();
    }

    $response = post(route('verification.send'));

    $response->assertStatus(429);
});

it('marks the email as verified when visiting the signed link', function (): void {
    $user = User::factory()->unverified()->create();

    actingAs($user);

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        [
            'id' => $user->getKey(),
            'hash' => sha1($user->getEmailForVerification()),
        ],
    );

    $response = $this->get($verificationUrl);

    $response->assertRedirect(config('fortify.home'));

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();

    $auditEvent = AuthEvent::query()->where('event_type', AuthEventType::EmailVerified)->sole();

    expect($auditEvent->user_id)->toBe($user->id);
    expect($auditEvent->identifier)->toBe($user->email);
});
