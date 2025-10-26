# Contributing to TravianT Authentication Platform

We welcome contributions that make the authentication, sitter, and alerting experience better. Please take a moment to review how we work before opening a pull request.

## Getting Started

- Fork the repository and create a topic branch from `main`.
- Copy `.env.example` to `.env` and follow the [local development steps](README.md#local-development).
- Familiarise yourself with the [architecture overview](README.md#architecture) and the existing [documentation hub](docs/README.md).

## Expectations

- **Discuss significant changes first.** Open an issue or a draft proposal for features that touch core flows, database schema, or deployment processes.
- **Follow Laravel conventions.** Use Form Requests for validation, queue long-running work, and keep business logic in dedicated services.
- **Keep PRs focused.** Provide a clear problem statement, solution summary, and testing notes.
- **Document as you go.** Update `/docs` when behaviour, APIs, or operational procedures change.
- **Test everything.** Add or adjust Pest tests, run `php artisan test --filter=<related>` locally, and `vendor/bin/pint --dirty` before pushing.

For deeper guidance (branch naming, code style, testing), refer to the detailed [docs/CONTRIBUTING.md](docs/CONTRIBUTING.md).

Thank you for helping build a resilient authentication platform!
