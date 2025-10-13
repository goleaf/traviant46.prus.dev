<?php

namespace App\Services\Auth;

use App\Models\Activation;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;

class LegacyLoginService
{
    public function attempt(string $identifier, string $password): ?LegacyLoginResult
    {
        $identifier = trim($identifier);
        $password = (string) $password;

        if ($identifier === '' || $password === '') {
            return null;
        }

        $user = $this->findActiveUser($identifier);

        if ($user instanceof User) {
            if (Hash::check($password, $user->password)) {
                return LegacyLoginResult::owner($user);
            }

            foreach ($this->resolveSitterCandidates($user) as $sitter) {
                if ($sitter instanceof User && Hash::check($password, $sitter->password)) {
                    return LegacyLoginResult::sitter($user, $sitter, $this->resolveSitterContext($user, $sitter));
                }
            }

            return null;
        }

        $activation = $this->findActivation($identifier);
        if ($activation instanceof Activation && Hash::check($password, $activation->password)) {
            return LegacyLoginResult::activation($activation);
        }

        return null;
    }

    protected function findActiveUser(string $identifier): ?User
    {
        return User::query()
            ->where(function ($query) use ($identifier) {
                $query->where('username', $identifier)
                    ->orWhere('email', $identifier);
            })
            ->first();
    }

    protected function findActivation(string $identifier): ?Activation
    {
        return Activation::query()
            ->where('used', false)
            ->where(function ($query) use ($identifier) {
                $query->where('name', $identifier)
                    ->orWhere('email', $identifier);
            })
            ->first();
    }

    /**
     * @return Collection<int, User>
     */
    protected function resolveSitterCandidates(User $user): Collection
    {
        $legacyIds = collect([$user->sit1_uid, $user->sit2_uid])
            ->filter()
            ->map(static fn ($id) => (int) $id);

        $legacySitters = $legacyIds->isEmpty()
            ? collect()
            : User::query()->whereIn('id', $legacyIds)->get();

        $delegatedSitters = $user->sitters()->get();

        return $legacySitters->merge($delegatedSitters)->unique('id');
    }

    /**
     * @return array<string, mixed>
     */
    protected function resolveSitterContext(User $account, User $sitter): array
    {
        if (isset($sitter->pivot) && (int) ($sitter->pivot->account_id ?? null) === $account->getKey()) {
            return [
                'permissions' => $this->normalisePermissions($sitter->pivot->permissions ?? []),
                'assignment_source' => 'delegated',
                'assignment_expires_at' => $this->normalisePivotDate($sitter->pivot->expires_at ?? null),
            ];
        }

        $assignment = $account->sitterAssignments()
            ->where('sitter_id', $sitter->getKey())
            ->first();

        if ($assignment) {
            return [
                'permissions' => $this->normalisePermissions($assignment->permissions ?? []),
                'assignment_source' => 'delegated',
                'assignment_expires_at' => $assignment->expires_at,
            ];
        }

        return [
            'permissions' => [],
            'assignment_source' => 'legacy',
            'assignment_expires_at' => null,
        ];
    }

    private function normalisePivotDate(mixed $value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            return Carbon::parse($value);
        }

        if (is_numeric($value)) {
            return Carbon::createFromTimestamp((int) $value);
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function normalisePermissions(mixed $permissions): array
    {
        if (is_string($permissions)) {
            $decoded = json_decode($permissions, true);

            if (is_array($decoded)) {
                $permissions = $decoded;
            }
        }

        if (! is_array($permissions)) {
            return [];
        }

        return array_values(array_map('strval', $permissions));
    }
}
