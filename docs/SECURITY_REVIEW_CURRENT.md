# Current security review — 4 October 2026

This is a local source review with isolated regression evidence, not a penetration-test certificate or approval to accept real funds.

| Finding | Treatment and evidence |
| --- | --- |
| Public document link and SPA fallback | Private uploads and authenticated field-based reader; containment, MIME allowlist, permission/ownership checks, no-store/nosniff, static-path denial. Preserved real JPEG hash verified. |
| Administrator/user actor confusion | Explicit user middleware, separate tokens/logout, API-path header selection, same-origin header restriction; actor-isolation tests and browser checks. |
| Administrator MFA absent | Encrypted setup/recovery material, password-confirmed enrollment, replay counter, single-use recovery, restricted enrollment tokens and secret redaction. Production default requires MFA. |
| Business scope and pending access | Business wallet isolation retained; pending dashboard skips restricted data; pending/suspended financial gates tested. Approval requires verified identity and cannot bypass suspension. |
| Floating-point financial calculations | Decimal fee, limits, OTP binding, funding and withdrawal arithmetic; exact provider/outgoing webhook numeric JSON serialization. Boundary and large-amount regressions. |
| Webhook authentication and provider assumptions | Monnify documented signature/event/reference handling; unsigned sandbox bypass removed; generic production operations require explicit endpoints. Unrelated refund events cannot credit wallets. |
| Existing-schema outbox mismatch | Additive repair for six columns missed by edited historical creation migration; data-preserving repeatable upgrade test and MariaDB regression. |
| Dependency advisories | Compatible updates, final Composer/npm audits report zero known advisories. Audit status is point-in-time and must be checked again for each release. |
| Misleading dashboard totals/health | Database aggregate totals and business wallets; unmonitored health shown explicitly. |

Retained controls include payout reservation/OTP locking, original-provider reconciliation, terminal transition protection, business-scoped API keys, outbound webhook SSRF/DNS pinning and scheduled recovery. Current regression reports cover these behaviors with synthetic data. Deployment crash tests and real provider certification remain required.

Residual release requirements:

- Contracted provider/KYC specifications, certified sandbox fixtures and authoritative reconciliation statements; generic configurable adapters are not certifications.
- Blueprint V2 pricing boundaries, fee ownership/caps and PayVessel scope.
- Actual hosting TLS, CORS, CSP, Redis/scheduler locks, queue crash recovery, backup restoration and mail-delivery acceptance.
- Browser tokens use localStorage. XSS could expose them; decide and validate an HttpOnly session design before a higher-assurance public launch.
- Approved legal/privacy documents, personal-data retention/access policies and complete user journey acceptance must be supplied and reviewed. Existing placeholder policy links are not approved policy documents.
- Financial test API keys remain disabled until sandbox ledgers and settlement are completely isolated.
- Report/UI formatting still uses floating-point presentation in some places; it must not be used as the authoritative ledger for provider reconciliation.

Your original database, uploaded files and .env were preserved. No real payment, deployment or outbound user messaging was performed. Apply the additive migrations after backup in staging; do not overwrite APP_KEY, seed an existing operational database or reset financial data. See STAGING_HANDOVER.md for operational steps.
