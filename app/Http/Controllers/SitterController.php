<?php

namespace App\Http\Controllers;

use App\Enums\SitterPermission;
use App\Models\SitterAssignment;
use App\Models\User;
use App\Services\Auth\SessionContextManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class SitterController extends Controller
{
    public function index(Request $request, SessionContextManager $contextManager): JsonResponse
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
            'acting_as_sitter' => $contextManager->actingAsSitter(),
            'acting_sitter_id' => $contextManager->sitterId(),
            'context' => $contextManager->toArray(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'sitter_username' => ['required', 'string', 'exists:users,username'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(SitterPermission::all())],
            'expires_at' => ['nullable', 'date'],
        ]);

        $user = $request->user();
        $sitter = User::where('username', $data['sitter_username'])->firstOrFail();

        abort_if($sitter->is($user), 422, __('You cannot assign yourself as a sitter.'));

        $permissions = $this->normalisePermissions($data['permissions'] ?? []);
        $assignment = SitterAssignment::updateOrCreate(
            [
                'account_id' => $user->getKey(),
                'sitter_id' => $sitter->getKey(),
            ],
            [
                'permissions' => empty($permissions) ? null : $permissions,
                'expires_at' => isset($data['expires_at']) ? Carbon::parse($data['expires_at']) : null,
            ]
        );

        return response()->json([
            'message' => __('Sitter assignment saved.'),
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

        return response()->json([
            'message' => __('Sitter removed.'),
        ]);
    }

    /**
     * @param array<int, string> $permissions
     * @return array<int, string>
     */
    private function normalisePermissions(array $permissions): array
    {
        $valid = array_intersect($permissions, SitterPermission::all());

        return array_values(array_unique($valid));
    }
}
