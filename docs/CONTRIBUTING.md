# Contributing

Thanks for helping move the TravianT authentication service forward! The project follows a Laravel-first workflow with strict coding and testing expectations. This guide captures the conventions you should apply before opening a pull request.

## Development Workflow

- **Discuss first:** Open an issue or draft proposal for features that change behaviour, data structures, or deployment processes.
- **Branch naming:** Use descriptive feature branches such as `feature/sitter-permissions` or `bugfix/redis-timeout`.
- **Small, reviewable PRs:** Keep pull requests focused on a single concern and describe both the problem and the solution in the body.
- **Changelog discipline:** Update relevant documentation (for example `/docs` or config READMEs) when behaviour or operational requirements change.

## Coding Standards

- **PSR-12 + Laravel Pint:** Run `vendor/bin/pint --dirty` before every commit. Do not disable Pint rules locally.
- **Type safety:** Use PHP 8 property promotion, scalar/return type hints, and useful PHPDoc array shapes when the code is not self-documenting.
- **Laravel conventions:** Prefer Eloquent relationships, avoid `DB::` for new code, and reach for Form Requests, policies, and queued jobs as documented in the existing codebase.
- **Frontend:** Use Tailwind CSS v4 utilities and Flux UI components that already ship with the application.
- **Naming & formatting:** Follow the patterns in sibling files; match Livewire component names, route naming conventions, and translation key casing.

## Testing Expectations

- **Automated tests are mandatory.** Add or update Pest tests that cover new behaviour and edge cases.
- **Run focused suites:** Execute `php artisan test` with the most relevant file or filter instead of the whole suite when iterating.
- **Database changes:** Provide seeds/factories when introducing models or tables; use transactions in tests and reset state via Pest's helpers.
- **Static analysis:** If you introduce complex domain logic, consider adding assertions or dedicated unit tests in `tests/Unit`.

## Git & Tooling Hygiene

- **Commits:** Use conventional, informative commit messages. Avoid committing generated assets or IDE metadata.
- **Semantic versioning:** Follow Conventional Commit prefixes (`feat:`, `fix:`, etc.) and add `!` or a `BREAKING CHANGE:` footer whenever the public API changes. The release automation infers SemVer bumps and changelog sections from these messages.
- **Environment files:** Never commit `.env` changes or secrets. Update `.env.example` when new configuration keys are required.
- **Dependencies:** Do not add Composer or npm packages without prior discussion. Keep lock files in sync when dependency changes are approved.
- **Linting scripts:** The repository provides `composer test`, `composer dev`, and Vite scripts—use them where applicable instead of ad-hoc commands.

## Documentation & Communication

- **Architecture docs:** If you extend system components or queues, update the relevant document under `/docs`.
- **Security-sensitive changes:** Coordinate with maintainers before publishing details publicly; follow the process in `SECURITY.md`.
- **Release notes:** Add upgrade instructions to the environment documentation when migrations or breaking changes are introduced.

By following these rules we keep the authentication service predictable and production-ready. Thanks again for contributing!
