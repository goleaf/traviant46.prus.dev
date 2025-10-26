<?php

declare(strict_types=1);

namespace App\Services\Communication;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class CommunicationPaginationService
{
    /**
     * @var array<string, int>
     */
    private const DEFAULTS = [
        'messages' => 20,
        'reports' => 20,
    ];

    public function paginate(Builder $query, User $user, string $context, int $page = 1): LengthAwarePaginator
    {
        $perPage = $this->perPage($user, $context);

        return $query->paginate(
            perPage: $perPage,
            columns: ['*'],
            pageName: "{$context}_page",
            page: $page,
        );
    }

    public function perPage(User $user, string $context): int
    {
        $default = self::DEFAULTS[$context] ?? 20;
        $configured = config("game.communication.pagination.$context");

        if (is_numeric($configured)) {
            $configuredPerPage = (int) $configured;

            if ($configuredPerPage >= 5 && $configuredPerPage <= 50) {
                return $configuredPerPage;
            }
        }

        return $default;
    }
}
