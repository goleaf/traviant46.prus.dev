# Project Analysis

## High-Level Architecture
- The application is a Laravel 12 project that migrated authentication features from the legacy Travian backend and retains Livewire-driven interfaces for future parity with legacy controllers.【F:docs/README.md†L1-L37】【F:docs/alliance-livewire-components.md†L1-L48】
- Legacy Travian game logic has been archived under `/_travian`, while new Laravel services reside in `app/`, aligning with the migration plan described in the root planning document.【F:AGENT.md†L1-L75】

## Backend Services
- Authentication relies on Laravel Fortify with custom guards to support administrator, multihunter, and sitter roles, preserving legacy behaviour documented in the README.【F:docs/README.md†L5-L37】
- Background processing is being modernised through Laravel's queue and scheduler system, recommending Redis or database drivers alongside structured job classes and Supervisor-managed workers.【F:docs/queue-system.md†L1-L76】
- Planned Livewire components will replace alliance-related controllers, encapsulating profile management, forum handling, diplomacy workflows, and topic/post lifecycles.【F:docs/alliance-livewire-components.md†L1-L92】

## Domain Data Model
- Database documentation outlines redesigned tables for communication, economy, hero systems, alliances, and combat, ensuring each table gains explicit naming, foreign keys, and timestamp conventions compatible with Laravel migrations.【F:docs/database/README.md†L1-L120】【F:docs/movement-combat-tables.md†L1-L120】
- Migration notes for movement and combat emphasise preserving troop conservation across `movement`, `enforcement`, `trapped`, and `units` tables, alongside marketplace shipment integrity in `send`.【F:docs/movement-combat-tables.md†L1-L120】

## Infrastructure & Deployment
- Infrastructure planning targets Laravel's scheduler, queue workers, Redis, and Supervisor for reliable processing, complemented by deployment steps detailed in the infrastructure stack overview.【F:docs/infrastructure-stack.md†L1-L120】【F:docs/queue-system.md†L1-L76】
- Background jobs documentation provides guidance on balancing queue priorities, using rate limits, and coordinating with scheduler tasks for resource ticks and event processors.【F:docs/background-jobs.md†L1-L112】

## Collaboration Guidelines
- Contribution instructions emphasise concise PR descriptions, while the migration plan in `AGENT.md` lists the broader goals and metrics for aligning legacy Travian systems with Laravel 12.【F:docs/CONTRIBUTING.md†L1-L2】【F:AGENT.md†L1-L228】

## Key Follow-Up Areas
- Complete Livewire component implementation for alliance features and other legacy flows to replace imperative controllers.【F:docs/alliance-livewire-components.md†L1-L92】
- Finalise database migration scripts that map the detailed schema redesign into Laravel migrations, ensuring data integrity and timestamp conversions across all tables.【F:docs/database/README.md†L1-L120】
- Harden queue and scheduler operations by setting up monitoring, failure handling, and environment variables outlined in the queue system documentation.【F:docs/queue-system.md†L1-L76】
