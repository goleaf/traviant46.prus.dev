<?php

namespace App\Http\Controllers;

use App\Models\SitterAssignment;
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
            ->map(function (SitterAssignment $assignment) {
                return [
                    'id' => $assignment->getKey(),
                    'sitter' => [
                        'id' => $assignment->sitter->getKey(),
                        'username' => $assignment->sitter->username,
                        'name' => $assignment->sitter->name,
                    ],
                    'permissions' => $assignment->permissions,
                    'expires_at' => optional($assignment->expires_at)->toIso8601String(),
                    'created_at' => optional($assignment->created_at)->toIso8601String(),
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

        $user = $request->user();
        $sitter = User::where('username', $data['sitter_username'])->firstOrFail();

        abort_if($sitter->is($user), 422, __('You cannot assign yourself as a sitter.'));

        $assignment = SitterAssignment::updateOrCreate(
            [
                'account_id' => $user->getKey(),
                'sitter_id' => $sitter->getKey(),
            ],
            [
                'permissions' => $data['permissions'] ?? null,
                'expires_at' => isset($data['expires_at']) ? Carbon::parse($data['expires_at']) : null,
            ]
        );

        return response()->json([
            'data' => $assignment->load('sitter'),
        ], 201);
    }

    public function destroy(Request $request, User $sitter): JsonResponse
    {
        $request->user()->sitters()->detach($sitter->getKey());

        SitterAssignment::query()
            ->where('account_id', $request->user()->getKey())
            ->where('sitter_id', $sitter->getKey())
            ->delete();

        return response()->noContent();
    }
}
