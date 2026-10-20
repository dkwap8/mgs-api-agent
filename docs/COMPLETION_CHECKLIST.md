# Completion checklist ? Blueprint V2, 5 October 2026

The entire supplied `docs/specifications/MGS_API_Platform_Blueprint_V2.docx` has been read, including its tables. [Requirement matrix](BLUEPRINT_V2_REQUIREMENT_MATRIX.md) maps every substantive requirement, named dashboard module, API route, security layer and operational deliverable to source evidence or an explicit blocker. [PowerShell handover](BLUEPRINT_V2_HANDOVER.md) gives commands in order and expected results.

**Current verification: pending user execution.** No build, test, dependency installation, migration, optimization, backup command, provider call or live database operation was executed during this work. Static source comparison does not establish runtime correctness. Earlier results in `docs/review` cover earlier code only. The previous checklist is retained at `docs/review/completion-checklist-before-blueprint-v2.md`.

- [x] Confirm Blueprint readability and read the entire document.
- [x] Compare all twenty sections with the project and record mismatches, future scope and external inputs.
- [x] Implement collection-rule selection, exact deduction and administrator pricing management with overlap rejection.
- [x] Prepare the five exact Blueprint fee tiers as repeatable **inactive drafts**; do not overwrite existing rules or activate guessed boundaries.
- [x] Record successful collection fees in a unique transaction-linked revenue record, atomic with credit; expose provider-cost coverage without inventing profit.
- [x] Add bearer-only lookup for newly rotated keys; retain legacy public-key authentication; support Blueprint HMAC and existing nonce signing with replay protection.
- [x] Scope API logs by business, capture known-key denied requests, and provide merchant log filters/pagination.
- [x] Add Blueprint customer verification and transaction verification route aliases using the existing scoped lifecycle.
- [x] Add canonical payment success/failure, payout success/failure and virtual-account-created outbox events; retain legacy collection.success subscriptions without duplicate success delivery.
- [x] Add account-bound, expiring, attempt-limited single-use bank email confirmation and 24-hour destination withdrawal holds.
- [x] Add contextual device/IP payout review holds and payout lifecycle audits.
- [x] Add administrator virtual-account, KYC review/retry, audit and webhook-delivery pages and permission checks.
- [x] Encrypt newly written provider configuration with APP_KEY-compatible legacy reads; recursively strip raw identifiers from new KYC responses and clear transient raw numbers after successful verification.
- [x] Replace sample merchant bell entries with owner-scoped stored notifications; add login/key/bank/payout email notifications.
- [x] Add full-query merchant dashboard aggregates, success rate and actual key access status; replace hardcoded developer documentation.
- [x] Enforce the existing maintenance setting while keeping administrator recovery/provider callbacks available.
- [x] Implement private consistent database snapshots and application health checks; schedule daily snapshots and health monitoring.
- [x] Write meaningful regressions and a forced in-memory PHPUnit configuration; leave execution to the user.
- [ ] User runs lint, isolated test suite, frontend build and route checks and sends results.
- [ ] User creates/accepts a restorable database and upload backup, reviews migration SQL and applies additive migrations in a disposable restored staging database first.
- [ ] User accepts browser workflows, security controls, queues/scheduler and database preservation results.
- [ ] Confirm pricing below ?100, decimal tier gaps, provider costs and KYC/payout charge policies.
- [x] User approved Laravel 13; update both Blueprint stack entries and preserve the supplied Word file in specifications/archive.
- [ ] Supply approved provider/KYC contracts, sandbox access and permitted fixtures; implement and certify exact adapters (including PayVessel if selected).
- [ ] Complete isolated provider test-mode provisioning/ledger after sandbox contract and simulation policy are agreed; test keys continue to fail closed.
- [ ] Supply deployment domain/topology, SMTP/SMS delivery configuration, host monitoring and encrypted off-server/weekly server backup destinations.
- [ ] Complete provider reversal/timeout certification, opening balance reconciliation and disaster recovery restore acceptance.
- [ ] Supply partnership/company profile, approved policies and launch approval. Future subscriptions/staff access are outside V1.

Checked implementation items mean source changes are written, **not verified**. Database, uploads, `.env` and APP_KEY were not edited. The Word specification now says Laravel 13 in both stack entries, as approved; the supplied Word file is archived intact. Existing bank accounts retain their existing usability; the hold applies to accounts added/reverified through the new protected flow. Existing API keys retain their public-key authentication until explicitly rotated. Legacy unscoped API logs are retained and excluded from business-visible log results.
