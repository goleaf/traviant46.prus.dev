<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Security\SignedCookieKeyRotator;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use RuntimeException;

class RotateSignedCookieKeysCommand extends Command
{
    protected $signature = 'security:rotate-cookie-keys
        {--force : Rotate even when no previous key set is configured}
        {--max-previous= : Override the maximum number of previous keys to retain}';

    protected $description = 'Rotate the signed cookie key set while preserving previous secrets for compatibility.';

    public function handle(Filesystem $files): int
    {
        $hasKeySet = count((array) config('app.previous_keys', [])) > 0;
        if (! $hasKeySet && ! $this->option('force')) {
            $this->components->warn('No APP_PREVIOUS_KEYS configured. Skipping signed cookie rotation.');

            return self::SUCCESS;
        }

        $maxPreviousOption = $this->option('max-previous');
        $maxPrevious = $maxPreviousOption !== null
            ? (int) $maxPreviousOption
            : (int) config('security.cookie_keys.max_previous', 5);

        $rotator = new SignedCookieKeyRotator(
            files: $files,
            envPath: base_path('.env'),
            maxPreviousKeys: $maxPrevious,
        );

        try {
            $rotator->rotate();
        } catch (RuntimeException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info('Signed cookie keys rotated successfully.');
        $this->components->warn('Restart any running workers to load the refreshed APP_KEY.');

        return self::SUCCESS;
    }
}
