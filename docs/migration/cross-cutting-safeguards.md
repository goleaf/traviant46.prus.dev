# Cross-Cutting Migration Safeguards

To ensure a safe decommissioning of the legacy PHP stack, these guardrails apply across every module migration. They cover the authentication, village management, and combat domains and must be extended to any new modules brought into scope.

## Module Migration Checklists

Maintain a living checklist per module and block legacy code deletion until every item below is complete and signed off by the owning squad:

- **Parity validation** – automated parity tests must cover both happy-path and edge-case flows against the legacy implementation with green results from the last seven days.
- **Observability readiness** – dashboards and alerts for latency, error rate, and throughput must exist in the Grafana "New Platform" folder, with runbooks linked from the on-call wiki.
- **Rollback path** – module-specific rollback scripts (database migrations, cache keys, queues) must be validated in staging and stored in the `ops/rollback` repository using the same tag as the release candidate.
- **Stakeholder approvals** – the product owner, QA lead, and on-call engineer must acknowledge the checklist in the project tracker and confirm readiness to remove the legacy handler.

### Module Owners

| Module | Team | Checklist Location |
| --- | --- | --- |
| Authentication | Identity & Security | `Runbook > Auth Laravel Cutover` |
| Village Management | Game Systems | `Confluence > Village Modernization` |
| Combat | Live Operations | `Runbook > Combat Engine` |

Keep the checklists updated during delivery so everyone can see the remaining work at a glance.

## Rollout Controls

- **Feature flags** – keep the existing routing toggles (via LaunchDarkly `migration.*` flags) active until two full sprints after cutover. Operators must be able to revert to the legacy handler within five minutes if severe regressions occur.
- **Progressive exposure** – expand traffic weights gradually (10% → 25% → 50% → 100%) while observing dashboards and error budgets.
- **Post-deployment verification** – run smoke tests and parity checks immediately after each exposure increase; roll back if KPIs degrade beyond the agreed thresholds.

Document any exceptions in the relevant module checklist and obtain Director of Engineering approval before proceeding.
