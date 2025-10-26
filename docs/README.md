# Authentication Backend

This repository now contains the Laravel 12 service that powers the TravianT authentication stack after migrating it from the former `backend/` directory. The application ships with [Laravel Fortify](https://laravel.com/docs/fortify) and a set of integrations that replicate the legacy `Model\LoginModel` behaviour while modernising the platform.

## Key features

- **Fortify authentication** with username or email based login plus Redis-backed session storage.
- **Legacy sitter support** so delegated accounts can sign in with their own password while we track the delegation context.
- **Role specific guards** for the special administrator (`legacy_uid = 0`) and multihunter (`legacy_uid = 2`) accounts.
- **Email verification** and password recovery flows with lightweight Blade views.
- **Multi-account detection** via IP logging, automatic alerts, and Redis sessions for continuity with the existing infrastructure.

## Documentation hub

- [Project migration analysis](./project-analysis.md) — status summary, risks, and next steps for the TravianT migration effort.

## Getting started

```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

The seed data will create example accounts:

| Username     | Email                     | Password   | Notes                |
|--------------|---------------------------|------------|----------------------|
| `admin`      | `admin@example.com`       | `Admin!234`| Administrator guard  |
| `multihunter`| `multihunter@example.com` | `Multi!234`| Multihunter guard    |
| `playerone`  | `player@example.com`      | *random*   | Delegated to both    |

Regular test accounts are also generated via the factory.

## Sitter management API

All sitter routes require an authenticated and verified session.

| Method | Route                | Description                                 |
|--------|---------------------|---------------------------------------------|
| GET    | `/sitters`          | List sitters assigned to the current user   |
| POST   | `/sitters`          | Assign or update a sitter (`sitter_username`)|
| DELETE | `/sitters/{sitter}` | Remove a sitter delegation                  |

Requests accept optional `permissions` arrays and `expires_at` timestamps (ISO8601).

## Multi-account monitoring

Successful logins write to the `login_activities` table (Redis sessions remain active). The `MultiAccountDetector` keeps a running set of `multi_account_alerts` whenever multiple accounts appear from the same IP, mirroring the behaviour of the legacy PHP stack.

## Testing

Run the application test suite with:

```bash
php artisan test
```

You may also execute static analysis or linting via `composer lint` if you add a custom script.

---

This project is released under the MIT license.
