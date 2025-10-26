<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Models\User;
use App\Services\Communication\CommunicationPaginationService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait InteractsWithCommunicationPagination
{
    protected function paginateCommunication(Builder $query, string $context, int $page = 1): LengthAwarePaginator
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return $query->paginate(20);
        }

        /** @var CommunicationPaginationService $service */
        $service = app(CommunicationPaginationService::class);

        return $service->paginate($query, $user, $context, $page);
    }
}
