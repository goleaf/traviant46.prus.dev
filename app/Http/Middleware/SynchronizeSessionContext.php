<?php

namespace App\Http\Middleware;

use App\Models\Game\Village;
use App\Models\User;
use App\Services\Auth\SessionContextManager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class SynchronizeSessionContext
{
    public function __construct(private SessionContextManager $contextManager)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $this->synchronizeAdminOverride($request);
        $this->synchronizeVillageSelection($request);

        return $next($request);
    }

    private function synchronizeAdminOverride(Request $request): void
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->isAdmin()) {
            return;
        }

        if ($request->filled('impersonate')) {
            $target = User::query()->find($request->integer('impersonate'));

            if ($target instanceof User && ! $target->is($user)) {
                $this->contextManager->enterAdminOverride($user, $target);
            }
        }

        if ($request->boolean('stop_impersonating')) {
            $this->contextManager->clearAdminOverride();
        }
    }

    private function synchronizeVillageSelection(Request $request): void
    {
        if (! $request->user() instanceof User) {
            return;
        }

        $villageId = $request->input('village_id');
        $kid = $request->input('kid');

        if ($villageId === null && $kid === null) {
            return;
        }

        $village = null;

        if ($villageId !== null) {
            $village = Village::query()->find((int) $villageId);
        } elseif ($kid !== null && Schema::hasColumn((new Village())->getTable(), 'kid')) {
            $village = Village::query()->where('kid', (int) $kid)->first();
        }

        if (! $village instanceof Village) {
            return;
        }

        if ($request->user()->can('switch', $village)) {
            $this->contextManager->setActiveVillage($village, $kid !== null ? (int) $kid : null);
        }
    }
}
