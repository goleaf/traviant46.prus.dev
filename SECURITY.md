# Security Policy

Security is a first-class concern for the TravianT authentication platform. This document explains how to report vulnerabilities and what to expect during the disclosure process.

## Reporting a Vulnerability

- Email the security desk at [security@traviant.com](mailto:security@traviant.com) with the subject line `SECURITY: <summary>`.
- Provide a detailed description, proof-of-concept, and any logs/screenshots that help reproduce the issue.
- Request our PGP key in the initial email if you prefer to encrypt subsequent messages.
- Do not create public issues or pull requests for vulnerabilities.

## Response Timeline

| Stage | Target |
|-------|--------|
| Acknowledge receipt | within 2 business days |
| Initial assessment & priority assignment | within 5 business days |
| Patch availability | within 14 business days for high/critical, 30 days for medium |
| Public disclosure | Coordinated with the reporter once a fixed release is available |

We will keep you updated about progress and may ask for additional information during triage.

## Scope

- Laravel authentication service and supporting infrastructure in this repository.
- Redis-based session storage and sitter delegation logic.
- CI/CD pipelines and deployment scripts contained here.

Out of scope:

- Third-party services not maintained by TravianT.
- Vulnerabilities requiring physical access to infrastructure.

## Patching & Releases

- Security fixes land on the `main` branch and are cherry-picked to maintained release branches.
- Each fix is accompanied by regression tests and documented in the release notes.
- We may issue emergency hotfix tags when an immediate deployment is necessary.

## Safe Harbour

If you adhere to this policy while researching, we will not pursue or support any legal action against you. Please avoid actions that could degrade service (e.g., DDoS, mass account creation) during testing.

Thank you for helping keep TravianT players safe!
