<?php

declare(strict_types=1);

use App\Enums\AuthEventType;
use App\Models\AuthEvent;
use App\Models\User;
use App\Notifications\QueuedPasswordResetNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

use function Pest\Laravel\post;

uses(RefreshDatabase::class);

dataset('invalidPasswordResetRequests', [
    'missing email' => [
        [],
        Fortify::email(),
        'Please enter the email address associated with your account.',
    ],
    'invalid email format' => [
        [Fortify::email() => 'not-an-email'],
        Fortify::email(),
        'Enter a valid email address.',
    ],
]);

it('sends a reset password notification to known users', function (): void {
    Notification::fake();

    $user = User::factory()->create();

    $response = post(route('password.email'), [
        'email' => $user->email,
    ]);

    $response->assertSessionHas('status', trans('passwords.sent'));
    Notification::assertSentTo($user, QueuedPasswordResetNotification::class, function (object $notification) {
        return $notification instanceof ShouldQueue;
    });
});

it('records an audit event when requesting a password reset link', function (): void {
    $user = User::factory()->create();

    post(route('password.email'), [
        'email' => $user->email,
    ])->assertSessionHas('status', trans('passwords.sent'));

    $event = AuthEvent::query()->latest('id')->first();

    expect($event)->not->toBeNull();
    expect($event?->event_type)->toBe(AuthEventType::PasswordResetRequested);
    expect($event?->user_id)->toBe($user->id);
    expect($event?->identifier)->toBe($user->email);
    expect($event?->meta['status'] ?? null)->toBe(Password::RESET_LINK_SENT);
});

it('throttles password reset requests after configured attempts', function (): void {
    $user = User::factory()->create();

    $maxAttempts = max(1, (int) (config('fortify.rate_limits.password_reset.max_attempts') ?? 5));
    $key = Str::lower($user->email).'|127.0.0.1';

    RateLimiter::clear($key);

    foreach (range(1, $maxAttempts) as $attempt) {
        post(route('password.email'), [
            'email' => $user->email,
        ])->assertSessionHas('status', trans('passwords.sent'));
    }

    $response = post(route('password.email'), [
        'email' => $user->email,
    ]);

    $response->assertSessionHasErrors(Fortify::email());
});

it('validates password reset request input', function (array $payload, string $errorField, string $expectedMessage): void {
    $response = $this->from(route('password.request'))->post(route('password.email'), $payload);

    $response->assertRedirect(route('password.request'));
    $response->assertSessionHasErrors([
        $errorField => fn (array $messages): bool => in_array($expectedMessage, $messages, true),
    ]);
})->with('invalidPasswordResetRequests');

it('resets the password when provided a valid token', function (): void {
    $user = User::factory()->create([
        'password' => Hash::make('OldPass#123UV'),
    ]);

    $token = Password::broker()->createToken($user);

    $response = post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'NewPassword#456WX',
        'password_confirmation' => 'NewPassword#456WX',
    ]);

    $response->assertRedirect(config('fortify.home'));
    $this->assertAuthenticatedAs($user->fresh());
    expect(Hash::check('NewPassword#456WX', $user->fresh()->password))->toBeTrue();

    $auditEvent = AuthEvent::query()->where('event_type', AuthEventType::PasswordResetCompleted)->sole();

    expect($auditEvent->user_id)->toBe($user->id);
    expect($auditEvent->identifier)->toBe($user->email);
});
