<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\SitterPermission;
use App\Enums\SitterPermissionPreset;
use App\Models\SitterDelegation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

use function collect;

class SitterController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $delegations = SitterDelegation::query()
            ->forAccount($request->user())
            ->with('sitter')
            ->orderByDesc('created_at')
            ->get()
            ->map(function (SitterDelegation $delegation) {
                $permissions = $delegation->permissions ?? null;
                $effectivePermissions = $permissions === null
                    ? collect(SitterPermission::cases())->map->value->all()
                    : collect($permissions)->map(static fn ($permission) => (string) $permission)->unique()->values()->all();
                $preset = SitterPermissionPreset::detectFromPermissions($permissions);

                return [
                    'id' => $delegation->getKey(),
                    'sitter' => [
                        'id' => $delegation->sitter->getKey(),
                        'username' => $delegation->sitter->username,
                        'name' => $delegation->sitter->name,
                    ],
                    'permissions' => $permissions === null ? null : $effectivePermissions,
                    'effective_permissions' => $effectivePermissions,
                    'preset' => $preset?->value,
                    'expires_at' => optional($delegation->expires_at)->toIso8601String(),
                    'created_at' => optional($delegation->created_at)->toIso8601String(),
                ];
            });

        return response()->json([
            'data' => $delegations,
            'acting_as_sitter' => (bool) $request->session()->get('auth.acting_as_sitter', false),
            'acting_sitter_id' => $request->session()->get('auth.sitter_id'),
            'available_permissions' => collect(SitterPermission::cases())->map(fn (SitterPermission $permission) => [
                'value' => $permission->value,
                'label' => $permission->label(),
            ])->values(),
            'presets' => collect(SitterPermissionPreset::cases())->map(fn (SitterPermissionPreset $preset) => [
                'value' => $preset->value,
                'label' => $preset->label(),
                'description' => $preset->description(),
                'permissions' => $preset->permissionValues(),
            ])->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $allowedPermissions = collect(SitterPermission::cases())->map->value->all();
        $allowedPresets = collect(SitterPermissionPreset::cases())->map->value->all();

        $data = $request->validate([
            'sitter_username' => ['required', 'string', 'exists:users,username'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in($allowedPermissions)],
            'expires_at' => ['nullable', 'date'],
            'preset' => ['nullable', 'string', Rule::in($allowedPresets)],
        ]);

        $owner = $request->user();
        $sitter = User::where('username', $data['sitter_username'])->firstOrFail();

        abort_if($sitter->is($owner), 422, __('You cannot assign yourself as a sitter.'));

        $preset = isset($data['preset']) ? SitterPermissionPreset::from($data['preset']) : null;
        $explicitPermissions = $data['permissions'] ?? null;

        if ($preset instanceof SitterPermissionPreset) {
            $permissions = $preset === SitterPermissionPreset::FullAccess
                ? null
                : $preset->permissionValues();
        } elseif ($explicitPermissions !== null) {
            $permissions = collect($explicitPermissions)
                ->map(static fn ($permission) => (string) $permission)
                ->unique()
                ->values()
                ->all();

            $representsFullAccess = collect($permissions)->sort()->values()->all() === collect($allowedPermissions)->sort()->values()->all();

            if ($representsFullAccess) {
                $permissions = null;
                $preset = SitterPermissionPreset::FullAccess;
            }
        } else {
            $permissions = null;
        }

        $delegation = SitterDelegation::query()->firstOrNew([
            'owner_user_id' => $owner->getKey(),
            'sitter_user_id' => $sitter->getKey(),
        ]);

        $delegation->permissions = $permissions;
        $delegation->expires_at = isset($data['expires_at']) ? Carbon::parse($data['expires_at']) : null;

        if (! $delegation->exists) {
            $delegation->created_by = $owner->getKey();
        }

        $delegation->updated_by = $owner->getKey();
        $delegation->save();
        $delegation->loadMissing('sitter');

        $resolvedPermissions = $delegation->permissions === null
            ? $allowedPermissions
            : collect($delegation->permissions)->map(static fn ($permission) => (string) $permission)->unique()->values()->all();

        $resolvedPreset = $preset ?? SitterPermissionPreset::detectFromPermissions($delegation->permissions);

        return response()->json([
            'data' => [
                'id' => $delegation->getKey(),
                'sitter' => [
                    'id' => $delegation->sitter->getKey(),
                    'username' => $delegation->sitter->username,
                    'name' => $delegation->sitter->name,
                ],
                'permissions' => $delegation->permissions === null ? null : $resolvedPermissions,
                'effective_permissions' => $resolvedPermissions,
                'preset' => $resolvedPreset?->value,
                'expires_at' => optional($delegation->expires_at)->toIso8601String(),
                'created_at' => optional($delegation->created_at)->toIso8601String(),
                'updated_at' => optional($delegation->updated_at)->toIso8601String(),
            ],
        ], $delegation->wasRecentlyCreated ? 201 : 200);
    }

    public function destroy(Request $request, User $sitter): JsonResponse
    {
        SitterDelegation::query()
            ->forAccount($request->user())
            ->forSitter($sitter)
            ->delete();

        return response()->noContent();
    }
}
