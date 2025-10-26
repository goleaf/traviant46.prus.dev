<?php

declare(strict_types=1);

use App\Actions\Fortify\PasswordValidationRules;
use App\Notifications\QueuedPasswordResetNotification;
use App\Notifications\QueuedVerifyEmailNotification;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

beforeEach(function () {
    config()->set('cache.default', 'array');
    config()->set('database.default', 'sqlite');
    config()->set('database.connections.sqlite.database', ':memory:');
});

it('prevents key regeneration when locked', function () {
    $originalKey = config('app.key');
    $originalLock = config('security.app_key.locked');
    $envPath = base_path('.env');
    $originalEnvContents = File::exists($envPath) ? File::get($envPath) : null;

    try {
        config()->set('security.app_key.locked', true);
        config()->set('app.key', 'base64:test-key-1234567890abcdefghi=');

        expect(Artisan::call('key:generate'))->toBe(0)
            ->and(config('app.key'))->toBe('base64:test-key-1234567890abcdefghi=');

        if ($originalEnvContents !== null) {
            expect(File::get($envPath))->toBe($originalEnvContents);
        }
    } finally {
        config()->set('app.key', $originalKey);
        config()->set('security.app_key.locked', $originalLock);
    }
});

it('applies configurable password policy', function () {
    $originalConfig = config('security.passwords');

    try {
        config()->set('security.passwords', [
            'min_length' => 12,
            'require_letters' => true,
            'require_mixed_case' => true,
            'require_numbers' => true,
            'require_symbols' => true,
            'uncompromised' => [
                'enabled' => false,
                'threshold' => 3,
            ],
        ]);

        $rulesProvider = new class
        {
            use PasswordValidationRules;

            public function rules(): array
            {
                return $this->passwordRules();
            }
        };

        $validator = Validator::make([
            'password' => 'Password1234',
            'password_confirmation' => 'Password1234',
        ], [
            'password' => $rulesProvider->rules(),
        ]);

        expect($validator->fails())->toBeTrue();

        config()->set('security.passwords.require_symbols', false);

        $validator = Validator::make([
            'password' => 'Password1234',
            'password_confirmation' => 'Password1234',
        ], [
            'password' => $rulesProvider->rules(),
        ]);

        expect($validator->fails())->toBeFalse();
    } finally {
        config()->set('security.passwords', $originalConfig);
    }
});

it('queues verification and password reset emails using configured queues', function () {
    $originalMailQueue = config('mail.queue');
    $originalMailConnection = config('queue.mail_connection');
    $originalMailQueueName = config('queue.mail_queue');

    try {
        config()->set('mail.queue', [
            'connection' => 'redis-mail',
            'name' => 'mail-out',
            'retry_after' => 120,
        ]);

        $verificationNotification = new QueuedVerifyEmailNotification;

        expect($verificationNotification->connection)->toBe('redis-mail')
            ->and($verificationNotification->queue)->toBe('mail-out')
            ->and($verificationNotification->viaQueues())->toBe(['mail' => 'mail-out']);

        config()->set('mail.queue', [
            'connection' => null,
            'name' => null,
            'retry_after' => null,
        ]);
        config()->set('queue.mail_connection', 'sqs-mails');
        config()->set('queue.mail_queue', 'fallback-mail');

        $resetNotification = new QueuedPasswordResetNotification('token-value');

        expect($resetNotification->connection)->toBe('sqs-mails')
            ->and($resetNotification->queue)->toBe('fallback-mail')
            ->and($resetNotification->viaQueues())->toBe(['mail' => 'fallback-mail']);
    } finally {
        config()->set('mail.queue', $originalMailQueue);
        config()->set('queue.mail_connection', $originalMailConnection);
        config()->set('queue.mail_queue', $originalMailQueueName);
    }
});
