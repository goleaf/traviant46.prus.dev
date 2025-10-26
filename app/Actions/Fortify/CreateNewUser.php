<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'username' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9_\-.]+$/', Rule::unique(User::class, 'username')],
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class, 'email'),
            ],
            'password' => $this->passwordRules(),
        ])->validate();

        $legacyUid = (int) User::query()->max('legacy_uid');
        $nextLegacyUid = $legacyUid + 1;

        while (User::isReservedLegacyUid($nextLegacyUid)) {
            $nextLegacyUid++;
        }

        return User::create([
            'legacy_uid' => $nextLegacyUid,
            'username' => Str::lower($input['username']),
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
        ]);
    }
}
