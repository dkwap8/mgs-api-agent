# Comprehensive Engineering Change Log

## Platform architecture

- Consolidated the application as a Laravel and Vue multi-business fintech platform.
- Added active-business request context and ownership validation.
- Added request IDs and standard API response envelopes.
- Added structured exception rendering for API clients.

## Multi-business financial isolation

- Added `business_id` ownership to wallets, transactions, payouts, settlement bank accounts, API keys, and related financial records.
- Enforced wallet uniqueness by merchant, business, and currency.
- Added migrations to backfill existing business ownership while preserving wallet balances and transaction history.
- Updated wallet credit, debit, reserve, capture, release, balance, and transaction operations to require merchant and business context.
- Removed merchant-only wallet creation and lookup escape paths.
- Scoped dashboard metrics, analytics, reports, payouts, collections, and API-key operations to the active business.
- Added frontend business switching that clears business-scoped state and reloads data using `X-Business-ID`.

## KYC and onboarding

- Added provider-based NIN and BVN verification abstraction.
- Added encrypted identity storage, hashes, retries, stale-processing recovery, and reconciliation.
- Added automatic virtual-account provisioning after successful verification.
- Integrated merchant business activation with owner customer records and queued KYC processing.

## Transactions, collections, and payouts

- Added direct business ownership to transactions and business-scoped transaction services.
- Added reporting indexes and canonical transaction status scopes.
- Hardened payout reservations, processing, retries, settlement selection, and refunds.
- Added configurable withdrawal controls and OTP verification.
- Added provider funding, health, reconciliation, priority optimisation, incidents, and cleanup workflows.

## Webhooks and idempotency

- Added queued webhook processing and retry commands.
- Added webhook signature and timestamp validation support.
- Added idempotency-key persistence and pruning.
- Added duplicate provider-reference protection for transactions.

## Notifications and API UX

- Added queued notification delivery and retry support.
- Added a unified API response contract with request IDs.
- Added reusable success, error, warning, information, confirmation, pending, processing, and loading modal behaviour.
- Replaced native browser alerts and confirmations with the central modal service.

## Frontend and UI/UX

- Added shared fintech-oriented UI styling, dark-mode support, responsive behaviour, accessible focus states, form states, skeletons, empty states, and reduced-motion handling.
- Improved authentication and password-reset form behaviour, including floating labels, password visibility, strength feedback, Caps Lock warning, validation, autocomplete, and loading states.
- Added business-aware Axios request handling and stale-state clearing during business switches.

## Testing and contracts

- Added contract tests for API response structure, sensitive serialization, notification metadata, and multi-business financial isolation.
- Performed PHP and JavaScript syntax checks during source preparation.
- Runtime test execution remains pending deployment verification as documented in `RELEASE_NOTES.md`.

## Cleanup

See `REMOVED_FILES.txt` for files removed from the cumulative source tree.


## 5 October 2026 ? Blueprint V2 implementation (unverified)

Read the full supplied Word document and create the complete requirement matrix, replace the completion checklist and current project status, and retain prior reports as historical evidence. The user approved Laravel 13; update both Word stack entries and archive the supplied original.

Implement collection pricing/draft tiers and separate collection revenues; bearer-only rotated-key lookup and Blueprint HMAC; scoped API logs and route aliases; canonical payment/payout/account events; bank confirmation and 24-hour holds; contextual risk review and payout audits; missing administrator modules; real merchant notices and aggregate dashboard data; private new provider config writes and KYC redaction/status repair; enforced maintenance and backup/application-health implementations. Prepare three additive migrations and isolated regression configuration. No runtime checks, builds, migrations, installations, optimization, backups, provider calls or database/environment/upload modifications performed. Follow BLUEPRINT_V2_HANDOVER.md and send results before claiming verification.
