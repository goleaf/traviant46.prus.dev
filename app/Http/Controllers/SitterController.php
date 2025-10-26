<?php

namespace App\Http\Controllers;

use App\Models\SitterDelegation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class SitterController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $assignments = $request->user()
            ->sitterAssignments()
            ->with('sitter')
            ->get()
            ->map(function (SitterDelegation $delegation) {
                return [
                    'id' => $delegation->getKey(),
                    'sitter' => [
                        'id' => $delegation->sitter->getKey(),
                        'username' => $delegation->sitter->username,
                        'name' => $delegation->sitter->name,
                    ],
                    'permissions' => $delegation->permissions,
                    'expires_at' => optional($delegation->expires_at)->toIso8601String(),
                    'created_at' => optional($delegation->created_at)->toIso8601String(),
                ];
            });

        return response()->json([
            'data' => $assignments,
            'acting_as_sitter' => (bool) $request->session()->get('auth.acting_as_sitter', false),
            'acting_sitter_id' => $request->session()->get('auth.sitter_id'),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'sitter_username' => ['required', 'string', 'exists:users,username'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $owner = $request->user();
        $sitter = User::where('username', $data['sitter_username'])->firstOrFail();

        abort_if($sitter->is($owner), 422, __('You cannot assign yourself as a sitter.'));

        $delegation = SitterDelegation::query()->firstOrNew([
            'owner_user_id' => $owner->getKey(),
            'sitter_user_id' => $sitter->getKey(),
        ]);

        $delegation->permissions = $data['permissions'] ?? null;
        $delegation->expires_at = isset($data['expires_at']) ? Carbon::parse($data['expires_at']) : null;

        if (! $delegation->exists) {
            $delegation->created_by = $owner->getKey();
        }

        $delegation->updated_by = $owner->getKey();
        $delegation->save();

        return response()->json([
            'data' => $delegation->load('sitter'),
        ], $delegation->wasRecentlyCreated ? 201 : 200);
    }

    public function destroy(Request $request, User $sitter): JsonResponse
    {
        $request->user()->sitters()->detach($sitter->getKey());

        SitterDelegation::query()
            ->forAccount($request->user())
            ->forSitter($sitter)
            ->delete();

        return response()->noContent();
    }
}
