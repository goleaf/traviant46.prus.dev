<?php

declare(strict_types=1);

use App\Services\Security\SignedCookieKeyRotator;
use Illuminate\Filesystem\Filesystem;

it('rotates the application key and preserves previous keys within the configured limit', function (): void {
    $filesystem = app(Filesystem::class);

    $temporaryEnv = tempnam(sys_get_temp_dir(), 'env');
    expect($temporaryEnv)->not->toBeFalse();

    $temporaryEnv = (string) $temporaryEnv;

    $originalKey = 'base64:original-key';
    $previousKeys = ['base64:legacy-key-one', 'base64:legacy-key-two'];

    $filesystem->put($temporaryEnv, implode(PHP_EOL, [
        'APP_KEY='.$originalKey,
        'APP_PREVIOUS_KEYS='.implode(',', $previousKeys),
    ]).PHP_EOL);

    $initialAppKey = config('app.key');
    $initialPrevious = config('app.previous_keys');

    config()->set('app.key', $originalKey);
    config()->set('app.previous_keys', $previousKeys);

    try {
        $rotator = new SignedCookieKeyRotator(
            files: $filesystem,
            envPath: $temporaryEnv,
            maxPreviousKeys: 3,
        );

        $rotator->rotate();

        $updatedEnv = $filesystem->get($temporaryEnv);
        expect($updatedEnv)->toContain('APP_KEY=')
            ->and($updatedEnv)->toContain('APP_PREVIOUS_KEYS=');

        $lines = collect(explode(PHP_EOL, trim($updatedEnv)))
            ->mapWithKeys(function (string $line): array {
                if ($line === '') {
                    return [];
                }

                [$key, $value] = explode('=', $line, 2);

                return [$key => $value];
            });

        $newKey = $lines->get('APP_KEY');
        $retainedKeys = $lines->get('APP_PREVIOUS_KEYS');

        expect($newKey)
            ->toStartWith('base64:')
            ->not->toBe($originalKey);

        expect($retainedKeys)
            ->toBe('base64:original-key,base64:legacy-key-one,base64:legacy-key-two');
    } finally {
        config()->set('app.key', $initialAppKey);
        config()->set('app.previous_keys', $initialPrevious);

        if ($filesystem->exists($temporaryEnv)) {
            $filesystem->delete($temporaryEnv);
        }
    }
});
