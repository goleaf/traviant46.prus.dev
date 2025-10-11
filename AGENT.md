# Project Migration Agent Handbook

## Comprehensive Task Documentation

### Objectives
- Modernize the existing Travian-like platform to align with the new modular architecture outlined in the 2024 infrastructure plan.
- Ensure continuity for active users during the migration by maintaining read operations in legacy systems until cutover.
- Reduce operational toil by automating deployment, data migration, and validation steps wherever possible.

### Scope
- Backend PHP services located under `App/`, `backend/`, `services/`, and `TaskWorker/`.
- Frontend Angular assets in `angularIndex/` and accompanying static entry points (`index.php`, `docs/`, `resources/`).
- Database assets defined in `database/`, `main.sql`, and incremental migrations under `scripts/` and `sections/`.
- Infrastructure and automation scripts within `main_script`, `main_script_dev`, and `scripts/`.

### Constraints
- All production deployments must target the Kubernetes clusters described in the `docs/platform-architecture.md` (see repo documentation) using Helm v3.12 or later.
- Legacy cron-based triggers must be replaced with event-driven jobs compatible with the new task orchestration service.
- Backwards compatibility must be preserved for API consumers for at least one release cycle (two weeks) post cutover.

### Deliverables
1. Fully migrated codebase with updated directory structure and namespaces.
2. Database schema aligned with the normalized redesign described below.
3. Comprehensive automated test suite with integration coverage for migration-critical flows.
4. Deployment and rollback runbooks updated to reflect new infrastructure.
5. Stakeholder-approved Laravel 12 migration plan (see `travian-to.plan.md`).

## Migration Checklist (200+ Items)
1. Confirm migration window approval from stakeholders.
2. Validate change management ticket is in "Approved" state.
3. Notify SRE team of planned migration timeline.
4. Backup current production database snapshot.
5. Verify snapshot integrity checksum.
6. Export legacy configuration files from `/config/`.
7. Archive exported configs to secure storage.
8. Freeze non-essential deployments for migration window.
9. Disable legacy cron jobs that conflict with migration.
10. Confirm monitoring alerts are in maintenance mode.
11. Document legacy service endpoints.
12. Review feature flags impacting migration scope.
13. Validate on-call rotations are updated for migration support.
14. Ensure runbooks reference new escalation paths.
15. Review AGENT.md with migration team.
16. Update project wiki with migration objectives.
17. Assign roles for cutover tasks.
18. Distribute contact list for cross-team support.
19. Schedule kickoff meeting with engineering leads.
20. Align QA team on testing timelines.
21. Provision staging environment mirroring production.
22. Deploy latest code to staging.
23. Run database migration dry run in staging.
24. Record metrics baseline before migration.
25. Capture application performance benchmarks.
26. Validate external integrations availability.
27. Confirm third-party vendors are notified.
28. Audit API rate limits for migration activities.
29. Validate data retention policies for backups.
30. Confirm encryption keys are valid and rotated.
31. Review IAM policies for migration scripts.
32. Ensure CI pipelines reference new branches.
33. Update branch protection rules for migration branch.
34. Tag repository with pre-migration release.
35. Create migration branch from latest main.
36. Merge outstanding hotfixes into migration branch.
37. Reconcile dependency versions with target stack.
38. Update composer dependencies to supported versions.
39. Validate PHP runtime version compatibility.
40. Update Node.js version for Angular build pipeline.
41. Confirm Docker base images match new runtime.
42. Scan Dockerfiles for deprecated directives.
43. Update Dockerfiles with multi-stage builds.
44. Validate container security scanning results.
45. Implement reproducible builds for containers.
46. Update Kubernetes manifests for new services.
47. Ensure namespace segregation per environment.
48. Configure service mesh annotations.
49. Review ingress controller settings.
50. Verify TLS certificates for new domains.
51. Update DNS records for new endpoints.
52. Schedule DNS TTL reduction before cutover.
53. Validate CDN configuration for static assets.
54. Pre-warm CDN caches with new assets.
55. Update load balancer target groups.
56. Confirm health check endpoints exist.
57. Update autoscaling policies for new services.
58. Validate resource requests and limits.
59. Ensure observability agents are configured.
60. Update logging pipelines for new services.
61. Configure tracing instrumentation endpoints.
62. Review metric dashboards for new labels.
63. Update alert thresholds for new baselines.
64. Create migration playbook in incident response tool.
65. Draft rollback plan with decision criteria.
66. Obtain sign-off on rollback plan.
67. Prepare communication templates for stakeholders.
68. Schedule status updates during migration.
69. Verify support portal announcements drafted.
70. Update end-user documentation with timeline.
71. Validate localization requirements for notifications.
72. Confirm legal review for downtime notices.
73. Stage updated API documentation.
74. Ensure API client SDKs are versioned.
75. Update package registry entries for SDKs.
76. Publish release candidate notes.
77. Execute linting across PHP codebase.
78. Execute linting across TypeScript codebase.
79. Resolve lint issues identified.
80. Run unit test suite for backend.
81. Run unit test suite for frontend.
82. Address failing unit tests.
83. Run integration tests for key workflows.
84. Validate e2e tests in staging.
85. Confirm test data refresh in staging database.
86. Sanitize production data for staging use.
87. Verify anonymization scripts executed.
88. Run data consistency checks post-refresh.
89. Validate search index rebuild in staging.
90. Confirm cache warming tasks in staging.
91. Execute performance tests against staging.
92. Compare performance metrics to baseline.
93. Optimize identified performance regressions.
94. Review error logs post-testing.
95. Resolve critical errors before proceeding.
96. Conduct security penetration test on staging.
97. Remediate vulnerabilities identified.
98. Validate compliance checks (PCI/GDPR).
99. Ensure auditing trails enabled.
100. Update privacy impact assessment documentation.
101. Review feature toggles for phased rollout.
102. Configure progressive rollout parameters.
103. Validate canary deployment strategy.
104. Script automated database migration steps.
105. Script automated database rollback steps.
106. Verify migration scripts idempotency.
107. Store scripts in version control.
108. Tag scripts with semantic versioning.
109. Dry run scripts against sanitized dataset.
110. Validate transactional boundaries in scripts.
111. Ensure foreign keys enforced post-migration.
112. Verify triggers/constraints re-created.
113. Document manual intervention steps.
114. Configure monitoring on migration scripts.
115. Obtain DBA approval for scripts.
116. Review storage capacity for migration.
117. Validate disk throughput requirements.
118. Coordinate with infrastructure team on capacity.
119. Confirm backup retention after migration.
120. Archive legacy database schema diagrams.
121. Generate new schema diagrams.
122. Review schema with engineering team.
123. Document schema changes in release notes.
124. Map entity relationships to service domains.
125. Update ORM models to new schema.
126. Regenerate code from ORM if required.
127. Validate repository patterns align with schema.
128. Update service layer contracts.
129. Refactor controllers to new services.
130. Update dependency injection bindings.
131. Review caching strategies for new schema.
132. Adjust cache invalidation logic.
133. Update search indexing pipeline.
134. Reconfigure message queues for new events.
135. Validate message schema changes.
136. Update analytics event tracking.
137. Confirm BI dashboards accommodate changes.
138. Update data warehouse ingestion pipelines.
139. Validate ETL jobs against new schema.
140. Coordinate with analytics team for validation.
141. Update feature flags for new data model.
142. Review API payload contracts.
143. Update API versioning strategy if needed.
144. Communicate breaking changes to partners.
145. Update mobile app dependencies.
146. Validate backward compatibility for mobile.
147. Update GraphQL schema definitions.
148. Regenerate GraphQL clients.
149. Test GraphQL queries/mutations.
150. Validate REST endpoints coverage.
151. Update Postman collections.
152. Share updated collections with QA.
153. Document migration assumptions.
154. Validate domain events documentation.
155. Update sequence diagrams.
156. Review architecture decision records.
157. Record new decisions in ADRs.
158. Review security threat models.
159. Update threat models with new components.
160. Validate data classification for new tables.
161. Ensure encryption at rest enabled.
162. Review key management policies.
163. Update secrets management configuration.
164. Rotate secrets post-migration.
165. Validate secret distribution pipelines.
166. Update environment variable documentation.
167. Confirm config management templates updated.
168. Test configuration rollout via automation.
169. Document configuration rollback steps.
170. Review logging schemas for structured fields.
171. Update logging masks for sensitive data.
172. Validate log retention policies.
173. Ensure SIEM integrations updated.
174. Test alert routing for new services.
175. Update pager policies.
176. Conduct migration readiness review.
177. Obtain go/no-go approval.
178. Start migration bridge call.
179. Disable write access in legacy system.
180. Confirm no active transactions remain.
181. Run final database backup.
182. Execute migration scripts in production.
183. Monitor migration script logs in real time.
184. Validate row counts post-migration.
185. Run referential integrity checks.
186. Rebuild indexes if required.
187. Re-enable write access on new system.
188. Run smoke tests on new environment.
189. Validate key API endpoints manually.
190. Confirm background jobs running.
191. Monitor error rates and latency.
192. Incrementally route traffic to new system.
193. Validate canary metrics.
194. Proceed to full traffic cutover.
195. Monitor system for stability period (2 hours).
196. Confirm monitoring alerts back to normal.
197. Announce migration completion to stakeholders.
198. Update status page to "Operational".
199. Close maintenance window notifications.
200. Capture lessons learned during debrief.
201. Update documentation with post-migration changes.
202. Archive legacy artifacts.
203. Tag repository with post-migration release.
204. Close change management ticket.
205. Celebrate with team.

## File Mapping Reference (Old → New)
| Legacy Path | New Path | Notes |
|-------------|----------|-------|
| `App/Http/Controllers` | `services/api/controllers` | Consolidated REST controllers with domain-specific namespaces. |
| `App/Models` | `services/domain/models` | Models aligned with bounded contexts. |
| `backend/cron` | `services/jobs/schedulers` | Replaced cron scripts with event-driven jobs. |
| `TaskWorker/` | `services/workers/async` | Workers rehomed under async job framework. |
| `angularIndex/src` | `resources/web/angular` | Angular assets relocated with build tooling. |
| `index.php` | `resources/web/public/index.php` | Entrypoint moved to public webroot with front controller pattern. |
| `database/migrations` | `database/legacy_migrations` | Legacy migrations archived for reference. |
| `scripts/` | `infrastructure/scripts` | Infrastructure and automation scripts centralized. |
| `main.sql` | `database/schema/base_schema.sql` | Base schema reauthored with normalized design. |
| `main_script` | `infrastructure/migration/main` | Primary migration automation suite. |
| `main_script_dev` | `infrastructure/migration/dev` | Dev-focused migration utilities. |
| `docs/` | `documentation/` | Documentation reorganized by domain. |

## Database Schema Redesign Specifications

### Guiding Principles
- Normalize core gameplay entities to 3NF to reduce duplication and update anomalies.
- Introduce audit tables for critical state transitions (villages, troops, resources).
- Enforce referential integrity with foreign keys and cascading rules.
- Optimize read performance with materialized views for leaderboard and alliance stats.

### Core Tables
- **players**: stores canonical user accounts with authentication metadata, replacing `users`.
- **player_profiles**: optional demographic and display preferences linked to `players`.
- **villages**: normalized representation of player settlements with geospatial indexing.
- **village_resources**: tracks per-resource quantities with historical snapshot support.
- **troop_types**: reference data for unit categories with balance parameters.
- **troop_stacks**: mapping of troops owned by a village, replacing legacy denormalized arrays.
- **alliances**: organizational grouping with governance metadata.
- **alliance_members**: join table linking `players` to `alliances` with role-based permissions.
- **market_trades**: normalized trades with buyer/seller foreign keys and escrow metadata.
- **messages**: asynchronous communication with thread grouping and retention policies.
- **battle_reports**: condensed summary of combat outcomes with JSON payload for replay.

### Support Tables
- **resource_ledger**: append-only ledger for resource transactions to support auditing.
- **event_queue**: durable queue for asynchronous game events awaiting processing.
- **achievement_unlocks**: linking players to milestone achievements.
- **notification_settings**: per-player notification preferences.
- **session_tokens**: secure session management replacing legacy plaintext tokens.

### Views and Materializations
- **leaderboard_view**: aggregated player performance metrics refreshed hourly.
- **alliance_power_view**: computed alliance strength metrics based on member stats.
- **economy_snapshot_view**: aggregated resource production/consumption rates.

### Migration Considerations
- Use temporary staging tables to transform denormalized arrays into normalized rows.
- Apply batch processing with chunked transactions to avoid long locks.
- Validate counts between legacy and new tables post-transform.
- Rebuild indexes incrementally to mitigate downtime.

## Service Layer Architecture

### Overview
- Adopt a domain-driven service layer with bounded contexts: Accounts, Economy, Warfare, Communication, and Alliance Management.
- Each bounded context exposes an internal service interface and optional external API facade.

### Components
1. **API Gateway**: Kubernetes ingress (Envoy-based) routing to context-specific APIs.
2. **Accounts Service**: Handles authentication, authorization, and profile management.
3. **Economy Service**: Manages resource production, storage, and trades with ledger integration.
4. **Warfare Service**: Processes troop movements, battles, and generates battle reports.
5. **Communication Service**: Manages messaging, notifications, and event subscriptions.
6. **Alliance Service**: Coordinates alliance formation, membership, and governance.
7. **Event Processor**: Consumes `event_queue` to orchestrate asynchronous game events.
8. **Data Sync Service**: Handles ETL flows to analytics and data warehouse targets.

### Cross-Cutting Concerns
- Shared libraries for logging, telemetry, and error handling via PSR-3 compliant interfaces.
- Centralized configuration via environment-specific manifests served by Consul.
- Service-to-service communication secured with mTLS provided by service mesh.
- Feature flagging integrated through LaunchDarkly SDK wrappers.

### Deployment Model
- Each service packaged as container image with dedicated Helm chart.
- CI/CD pipeline executes unit, integration, and contract tests prior to deployment.
- Blue/green deployment strategy with automated health verification and rollback hooks.

## Testing Requirements

### Testing Strategy
- **Unit Tests**: Minimum 80% coverage for domain logic in each bounded context.
- **Integration Tests**: Validate service interactions with real database and messaging infrastructure using docker-compose orchestration.
- **Contract Tests**: Pact-based tests to ensure backward compatibility for REST and GraphQL endpoints.
- **End-to-End Tests**: Cypress suite covering critical user journeys (login, village management, trade, battle, messaging).
- **Performance Tests**: Gatling scenarios simulating peak concurrent users with SLA thresholds (p95 < 200ms for key APIs).
- **Security Tests**: Automated dependency scanning (Snyk), container scanning (Trivy), and DAST (OWASP ZAP) before release.

### Test Environments
- **Development**: Local Docker environment with mocked external services.
- **Staging**: Environment mirroring production infrastructure for final validation.
- **Performance**: Scaled staging clone dedicated to load and stress testing.
- **Production**: Post-deployment smoke tests and continuous synthetic monitoring.

### Acceptance Criteria
- All critical defects (priority 1 and 2) resolved before production cutover.
- No open security vulnerabilities with CVSS > 7.0.
- Performance metrics meeting or exceeding SLAs.
- Sign-off from QA, SRE, and Product stakeholders.

