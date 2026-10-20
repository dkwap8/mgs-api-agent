> Current whole-project status, pending migrations and blockers: [PROJECT_STATUS.md](PROJECT_STATUS.md). This older checkpoint is historical evidence; it is not a production-readiness claim.

# Completion checklist — 4 October 2026

This checklist tracks the current local checkout. Historical test reports are not current verification. The Platform Blueprint V2 has not been found locally; commercial tier boundaries and contracted provider behavior must not be guessed.

| Requirement | Initial finding | Acceptance / status |
| --- | --- | --- |
| Source checkpoint, existing database/uploads/environment preservation | No Git repository; source backup required | Backup in system temporary directory; no live migrations or data resets permitted |
| Dedicated `/admin-panel` and `/user/login` login routes | Shared public administrator selector; old `/admin` and `/merchant` browser routes | Implement separate contexts, deliberate legacy redirects and backend actor checks; test |
| Administrator/user session separation | Separate models/tokens exist; user endpoints accept a generic Sanctum actor | Explicit user middleware, request token selection and isolated logout handling; test |
| Registration, OTP, password recovery | Backend implemented; login lacks MFA fields | Inspect recovery/OTP controls, complete login MFA UI and test |
| Administrator MFA | No administrator MFA setup/login enforcement | Add encrypted secret, confirmation, recovery codes and backend controls; test upgrade |
| Multiple businesses and wallets | Business ownership migrations and wallet service already installed | Run isolation regressions; no arbitrary merchant wallet fallback |
| Pending business dashboard | Calls restricted customer endpoint while pending | Read onboarding state first; skip restricted requests; retain financial gate |
| Activation approval/rejection/suspension | BusinessLifecycleService installed | Run administrator workflow tests; verify KYC requirements |
| Private activation documents | Public disk and missing link caused SPA fallback; link repaired earlier | Private new uploads; authorized field-based access; legacy containment and static-path protection; no file deletion |
| NIN/BVN KYC | Generic HTTP adapter and asynchronous lifecycle installed | Mocked tests possible; contracted specification/credentials and real fixtures required |
| Customers and virtual accounts | Implemented controllers/services; production fake-account guard installed | Isolation and provider failure tests; external certification required |
| Collections and credits | Duplicate credit/outbox safety installed | Exact amount handling and duplicate/out-of-order regression tests |
| Wallets, ledger, fees, statements | Wallet decimal arithmetic installed; fee calculations still float | Convert critical calculations to exact decimals; pricing boundaries need blueprint |
| Settlement bank verification | Provider verification installed | Verify mocked failure/success behavior; real provider validation outstanding |
| Withdrawal PIN/OTP/limits | Bound OTP, reservation lock and concurrency regression installed | Run isolated tests and two-process regression when disposable MySQL available |
| Payout approvals and reconciliation | Uncertain outcomes reserved; original provider binding enforced | Run timeout/duplicate/terminal transition regressions; crash tests required |
| API keys and developer docs | Business-scoped keys, test-key fail-closed guard installed | Retain disabled test operations until sandbox isolation verified |
| Provider integrations | Generic adapters, no local private contracts found | Official documentation review; never claim mocked tests are certification |
| Webhooks | HMAC/replay checks and SSRF/DNS pinning/outbox installed | Run signature, duplicate and recovery tests; review logs |
| Notifications/support/reports/audit | Controllers, queues and audit identity fixes installed | Test primary operations, permission coverage and sensitive serialization |
| Queue/scheduler/recovery | Scheduled reconciliation and retry commands installed | Check queues/timeouts and topology; deployed failure acceptance remains required |
| Responsive UI and themes | Existing light/dark layouts | Build, navigation checks and browser evidence where tools available |
| Security/dependencies/runtime | Existing hardening incomplete | Dependency audit, authorization tests, redaction and production config review |
| Migration/upgrade safety | Portable migrations and isolated SQLite tests present | Clean migration plus disposable populated-schema upgrade; never live resets |
| Deployment handover | Existing docs and old ZIP may be stale | Updated checklist/evidence/runbook, sanitized example and clean source ZIP |

## External inputs

- Platform Blueprint V2, including exact collection pricing tiers, boundaries, caps, charge ownership and PayVessel scope.
- Contracted provider and KYC specifications, sandbox credentials and permitted certification fixtures.
- Staging topology/domain/mail delivery and disposable MySQL availability for deployed acceptance checks.
- Authoritative provider statements for reviewing existing uncertain payments; no live payments or refunds are authorized.

## Current evidence

### Implemented and verified in this checkout

- Dedicated administrator/user browser routes and immutable login contexts; deliberate legacy redirects; unknown routes return errors. Backend rejects wrong actor types; logout and request headers keep contexts separate.
- Private authorized activation preview/download, private new uploads and legacy upload containment. Missing files and traversal return errors. Direct storage access is blocked; files remain intact. Approval uses an information confirmation instead of the misleading warning styling; approval/rejection dialogs close after successful operations.
- Pending-business dashboard skips restricted customer calls and uses the active business wallet. Administrator totals use aggregate queries rather than the first paginated page. Health is explicitly unmonitored.
- Administrator MFA encrypted setup, monotonic TOTP replay prevention, single-use recovery codes, restricted enrollment tokens, secret redaction and token revocation. Additive upgrade passed against a copy of the existing schema and preserved a synthetic administrator fixture.
- Exact decimal fee/funding/withdrawal calculations, OTP amount binding and numeric provider JSON serialization. Invalid fractional kobo and unsafe large floats are rejected. Monnify signature/event/reference mapping and v2 name enquiry are covered by fixtures; generic production endpoints require explicit contracts.
- Latest full SQLite run: 69 tests, 68 passed, 261 assertions, one skipped, zero failures/errors (completion-sqlite-final.xml). Upgraded MariaDB schema: all 69 tests passed, 262 assertions, zero failures/errors/risky tests (completion-mysql-final.xml). These are local/mock checks, not provider certification.
- Source PHP lint passed (php-lint-final.json). Frontend production build passed. Both final dependency audits report zero known advisories. Eight browser checks passed, including separate login contexts, mobile dark mode, pending dashboard, authenticated Preview and Download (synthetic API fixtures). Actual Laravel 404/authorization behavior is covered by PHPUnit.
- Two-process MariaDB withdrawal regression passed: one accepted request, one limit rejection, exact wallet/reservation preserved (concurrency-final.txt). The original JPEG is served by the document reader as HTTP 200 image/jpeg with matching hash and 923,528 bytes (preserved-document.json); .env hash also matches the starting checkpoint.
- The upgraded schema regression exposed six missing webhook-outbox columns. A repeatable additive forward migration repairs them and preserves existing records. Final column comparison reports no missing columns (schema-columns-final.json). KYC-pending approval and suspension-bypass regressions are covered.

### Remaining acceptance and external blockers

The rows above describe initial findings and acceptance criteria; they do not imply all platform requirements are complete. Missing Blueprint V2 blocks commercial tier/boundary acceptance. Contracted provider/KYC specifications and sandbox fixtures block certification. Test API keys remain disabled for financial operations. Deployed queue crash recovery, reconciliation statements, mail, TLS/CSP and complete UI acceptance require a configured isolated staging environment. Browser authenticated journeys use synthetic mocked API responses. LocalStorage token storage and remaining presentation/configuration float boundaries require a documented release decision.

Read STAGING_HANDOVER.md for exact upgrade, deployment and recovery steps. No live deployment or payment was performed. The original database has not received the new MFA migration; apply additive migrations after an approved backup in staging before administrator login acceptance.

Earlier in this conversation the legacy image was verified present (923,528 bytes); restoring `public/storage` returned HTTP 200 `image/jpeg`, byte-identical to the stored upload. This establishes the redirect cause, but public serving is being replaced to meet private-document requirements.
