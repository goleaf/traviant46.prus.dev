<?php

declare(strict_types=1);

namespace App\Support\Travian;

use InvalidArgumentException;

class TroopCatalog
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $definitions;

    /**
     * @param array<string, array<string, mixed>>|null $definitions
     */
    public function __construct(?array $definitions = null)
    {
        $this->definitions = $definitions ?? require base_path('app/Data/troops.php');
    }

    /**
     * @return array<string, mixed>
     */
    public function get(string $code): array
    {
        if (! isset($this->definitions[$code])) {
            throw new InvalidArgumentException(sprintf('Unknown troop code [%s].', $code));
        }

        return $this->definitions[$code];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function trainingOptions(string $code): array
    {
        $definition = $this->get($code);

        $options = $definition['training']['options'] ?? [];

        return is_array($options) ? array_values($options) : [];
    }

    /**
     * @return array<int, array<string, int>>
     */
    public function buildingRequirements(string $code): array
    {
        $definition = $this->get($code);

        $requirements = $definition['requirements']['buildings'] ?? [];

        return is_array($requirements) ? array_values($requirements) : [];
    }

    public function baseTime(string $code): int
    {
        $definition = $this->get($code);

        $baseTime = (int) ($definition['base_time'] ?? 0);

        if ($baseTime <= 0) {
            throw new InvalidArgumentException(sprintf('Troop [%s] has no valid base training time.', $code));
        }

        return $baseTime;
    }

    public function populationCost(string $code): int
    {
        $definition = $this->get($code);

        return (int) ($definition['population'] ?? 0);
    }
}
