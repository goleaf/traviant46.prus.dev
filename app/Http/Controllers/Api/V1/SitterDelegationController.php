<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Events\DelegationAssigned;
use App\Events\DelegationRevoked;
use App\Events\DelegationUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sitters\StoreSitterDelegationRequest;
use App\Models\SitterDelegation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SitterDelegationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User $owner */
        $owner = $request->user();

        Gate::authorize('viewAny', [SitterDelegation::class, $owner]);

        $delegations = SitterDelegation::query()
            ->with('sitter')
            ->forAccount($owner)
            ->get()
            ->map(fn (SitterDelegation $delegation): array => $this->transformDelegation($delegation));

        return response()->json([
            'data' => $delegations,
        ]);
    }

    public function store(StoreSitterDelegationRequest $request): JsonResponse
    {
        /** @var User $owner */
        $owner = $request->user();
        $sitter = $request->sitter();

        $delegation = SitterDelegation::query()
            ->forAccount($owner)
            ->forSitter($sitter)
            ->first();

        $wasExisting = $delegation instanceof SitterDelegation;

        if (! $wasExisting) {
            Gate::authorize('create', [SitterDelegation::class, $owner]);

            $delegation = new SitterDelegation([
                'owner_user_id' => $owner->getKey(),
                'sitter_user_id' => $sitter->getKey(),
                'created_by' => $owner->getKey(),
            ]);
        } else {
            Gate::authorize('update', $delegation);
        }

        $delegation->fill([
            'permissions' => $request->permissionSet(),
            'expires_at' => $request->expiresAt(),
            'updated_by' => $owner->getKey(),
        ]);

        $changes = $delegation->getDirty();

        $delegation->save();

        $delegation->loadMissing('sitter', 'owner');

        if (! $wasExisting) {
            event(new DelegationAssigned($delegation, $owner));
            $status = 201;
        } elseif ($changes !== []) {
            event(new DelegationUpdated($delegation, $owner, array_keys($changes)));
            $status = 200;
        } else {
            $status = 200;
        }

        return response()->json([
            'data' => $this->transformDelegation($delegation),
        ], $status);
    }

    public function destroy(Request $request, SitterDelegation $sitterDelegation): JsonResponse
    {
        $sitterDelegation->loadMissing('owner', 'sitter');

        Gate::authorize('delete', $sitterDelegation);

        $actor = $request->user();

        $sitterDelegation->delete();

        event(new DelegationRevoked($sitterDelegation, $actor instanceof User ? $actor : null, 'manual'));

        return response()->noContent();
    }

    /**
     * @return array<string, mixed>
     */
    protected function transformDelegation(SitterDelegation $delegation): array
    {
        $delegation->loadMissing('sitter');

        return [
            'id' => $delegation->getKey(),
            'sitter' => [
                'id' => $delegation->sitter->getKey(),
                'username' => $delegation->sitter->username,
                'name' => $delegation->sitter->name,
            ],
            'permissions' => $delegation->permissions->toArray(),
            'expires_at' => optional($delegation->expires_at)->toIso8601String(),
            'created_at' => optional($delegation->created_at)->toIso8601String(),
            'updated_at' => optional($delegation->updated_at)->toIso8601String(),
        ];
    }
}
