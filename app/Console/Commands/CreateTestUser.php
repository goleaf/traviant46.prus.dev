<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\StaffRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateTestUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'travian:create-test-user
        {--email=tester@example.com : Email address for the test user}
        {--username=tester : Username for the test user}
        {--name="Test Player" : Display name for the test user}
        {--password= : Explicit password to assign (defaults to a random 12 character string)}
        {--role=player : Staff role assigned to the user}
        {--force : Reset password and profile details if the user already exists}
        {--unverified : Leave the email address unverified}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Provision a seeded Travian test account with predictable credentials.';

    public function handle(): int
    {
        $email = strtolower((string) $this->option('email'));
        $username = Str::lower((string) $this->option('username'));
        $name = (string) $this->option('name');
        $password = $this->option('password');
        $force = (bool) $this->option('force');
        $verified = ! (bool) $this->option('unverified');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->components->error(sprintf('"%s" is not a valid email address.', $email));

            return self::INVALID;
        }

        $roleOption = strtolower((string) $this->option('role'));
        $role = StaffRole::tryFrom($roleOption);

        if (! $role instanceof StaffRole) {
            $validRoles = collect(StaffRole::cases())->map->value->implode(', ');
            $this->components->error(sprintf('Unknown role "%s". Valid roles: %s', $roleOption, $validRoles));

            return self::INVALID;
        }

        $user = User::query()->firstWhere('email', $email);

        if ($user !== null && ! $force) {
            $this->components->info('Test user already exists. Use --force to reset the credentials.');
            $this->displayUserDetails($user, '(existing password retained)');

            return self::SUCCESS;
        }

        $password = is_string($password) && $password !== '' ? $password : Str::password(12);

        if ($user === null) {
            $user = $this->createUser($username, $name, $email, $password, $role, $verified);

            $this->components->info('New test user created successfully.');
        } else {
            $this->updateUser($user, $username, $name, $password, $role, $verified);

            $this->components->info('Existing test user credentials refreshed.');
        }

        $this->displayUserDetails($user, $password);

        return self::SUCCESS;
    }

    private function createUser(
        string $username,
        string $name,
        string $email,
        string $password,
        StaffRole $role,
        bool $verified,
    ): User {
        $legacyUid = (int) User::query()->max('legacy_uid');
        $legacyUid = max($legacyUid, User::FIRST_PLAYER_LEGACY_UID) + 1;

        $user = User::factory()->create([
            'legacy_uid' => $legacyUid,
            'username' => $username,
            'name' => $name,
            'email' => $email,
            'role' => $role->value,
            'password' => Hash::make($password),
            'email_verified_at' => $verified ? Carbon::now() : null,
        ]);

        return $user;
    }

    private function updateUser(
        User $user,
        string $username,
        string $name,
        string $password,
        StaffRole $role,
        bool $verified,
    ): void {
        $user->forceFill([
            'username' => $username,
            'name' => $name,
            'role' => $role->value,
            'password' => Hash::make($password),
            'email_verified_at' => $verified ? Carbon::now() : null,
        ])->save();
    }

    private function displayUserDetails(User $user, string $password): void
    {
        $appUrl = rtrim(config('app.url'), '/');
        $loginUrl = $appUrl.'/login';

        $this->table(
            ['Username', 'Email', 'Password', 'Role', 'Login URL'],
            [[
                $user->username,
                $user->email,
                $password,
                $user->role?->value ?? StaffRole::Player->value,
                $loginUrl,
            ]],
        );
    }
}
