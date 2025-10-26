<?php

declare(strict_types=1);

namespace App\Services\Security;

use Illuminate\Filesystem\Filesystem;
use RuntimeException;

class SignedCookieKeyRotator
{
    public function __construct(
        protected Filesystem $files,
        protected string $envPath,
        protected int $maxPreviousKeys = 5,
    ) {}

    public function rotate(): string
    {
        if (! $this->files->exists($this->envPath)) {
            throw new RuntimeException(sprintf('Environment file not found at path [%s].', $this->envPath));
        }

        $currentKey = (string) config('app.key');
        if ($currentKey === '') {
            throw new RuntimeException('The APP_KEY configuration value must be set before rotating signed cookie keys.');
        }

        $previousKeys = (array) config('app.previous_keys', []);
        $newKey = 'base64:'.base64_encode(random_bytes(32));

        $updatedPrevious = $this->preparePreviousKeys($currentKey, $previousKeys);

        $this->persistEnv([
            'APP_KEY' => $newKey,
            'APP_PREVIOUS_KEYS' => implode(',', $updatedPrevious),
        ]);

        return $newKey;
    }

    /**
     * @param list<string> $previousKeys
     * @return list<string>
     */
    protected function preparePreviousKeys(string $currentKey, array $previousKeys): array
    {
        $keys = array_values(array_filter(array_unique([$currentKey, ...$previousKeys])));

        $limit = max($this->maxPreviousKeys, 0);
        if ($limit > 0) {
            $keys = array_slice($keys, 0, $limit);
        }

        return $keys;
    }

    /**
     * @param array<string, string> $values
     */
    protected function persistEnv(array $values): void
    {
        $contents = $this->files->get($this->envPath);

        foreach ($values as $key => $value) {
            $pattern = '/^'.preg_quote($key, '/').'=.*/m';
            $replacement = $key.'='.$value;

            if (preg_match($pattern, $contents)) {
                $contents = (string) preg_replace($pattern, $replacement, $contents);
            } else {
                $contents = rtrim($contents).PHP_EOL.$replacement.PHP_EOL;
            }
        }

        $this->files->put($this->envPath, $contents);
    }
}
