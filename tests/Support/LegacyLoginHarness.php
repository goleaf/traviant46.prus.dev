<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Models\Activation;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

class LegacyLoginHarness
{
    public function attempt(string $identifier, string $password): array
    {
        $identifier = trim($identifier);
        if ($identifier === '' || $password === '') {
            return ['mode' => null];
        }

        $user = $this->findActiveUser($identifier);
        if ($user instanceof User) {
            if (Hash::check($password, $user->password)) {
                return [
                    'mode' => 'owner',
                    'user' => $user,
                ];
            }

            foreach ($this->resolveSitterCandidates($user) as $sitter) {
                if ($sitter instanceof User && Hash::check($password, $sitter->password)) {
                    return [
                        'mode' => 'sitter',
                        'user' => $user,
                        'sitter' => $sitter,
                    ];
                }
            }

            return ['mode' => null, 'user' => $user];
        }

        $activation = $this->findActivation($identifier);
        if ($activation instanceof Activation && Hash::check($password, $activation->password)) {
            return [
                'mode' => 'activation',
                'activation' => $activation,
            ];
        }

        return ['mode' => null];
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
        return collect([$user->sit1_uid, $user->sit2_uid])
            ->filter()
            ->map(static fn ($id) => $id ? User::find($id) : null)
            ->filter();
    }
}
