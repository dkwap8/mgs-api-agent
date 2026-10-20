# MGS API review and completion plan

Reviewed 4 October 2026 against MGS API Platform Blueprint V2 and the supplied source archive.

## Outcome

The project has substantial functionality, but it should remain in staging. This package fixes reproducible payment correctness, authorization, administrator identity, and dashboard problems. It is not a certification of provider integrations, production security, regulatory compliance, or completion of every blueprint requirement. A reliable completion percentage cannot be derived from source files alone.

## Corrections delivered

| Area | Original problem | Delivered behavior |
| --- | --- | --- |
| Payout submission | A metadata flag could stand in for a verified OTP; PIN-only endpoint could bypass OTP | Requires a real, verified, unexpired OTP tied to user, business, bank and amount; reservation and OTP consumption are atomic |
| Payout daily limit | Parallel requests could check the same daily total before reserving | Business wallet lock serializes limit check and reservation; two-process regression included |
| Administrator payout actions | Processing payout could be reapproved, resent or refunded while provider outcome was unresolved | Approve/reject only pending; processing retry reconciles the original provider |
| Provider uncertainty | HTTP errors or incomplete responses could be treated as failure, releasing funds prematurely | Unknown outcomes retain reservation; no blind POST retry; reconcile before terminal action |
| Payout webhooks | Provider binding/reference and terminal transitions were insufficiently protected | Requires original provider binding; preserves provider reference; terminal states remain terminal |
| API access | Business API authentication was not correctly wired; test keys could touch live resources | Allowlisted merchant API operations use key-owned business; dashboard controls require login; test keys fail closed |
| Signature replay | Invalid signatures could interfere with nonce handling | HMAC checked before atomic nonce insertion |
| Idempotency | Concurrent first requests and retries after uncertainty could execute again | Unique firstOrCreate claim; uncertain/processing records remain blocked; pruning only completed records |
| Collections | Pending verification rolled back; success could lack amount; duplicate credit/delivery risks | Pending verification persists; explicit reference and exact amount required; one credit and durable webhook outbox |
| Merchant webhooks | Arbitrary destination could access internal services; retries could continue indefinitely | Public HTTPS:443 destination validation, DNS pinning, no redirects/proxy, bounded retries and pending-outbox recovery |
| Administrator identity | Admin IDs were written into user foreign keys; role permissions did not match seeded roles | Separate Admin actor columns and relations, role permission resolution, restricted provider writes |
| Settlement banks | Input could be treated as verified | Provider verification required for payout accounts; approval copies are initially unverified |
| Business boundaries | Merchant-wide wallet fallback and contact updates could cross business boundaries | Removes wallet fallback; selected-business contacts only; suspended accounts cannot request OTP |
| Dashboard settings | Unsupported profile changes appeared saved; business endpoint missing | Unsupported identity changes read-only/support review; working contact endpoint; backend rejects verified identity edits |
| Wallet arithmetic | Binary floating-point conversions could lose kobo on large balances | Decimal normalization and integer kobo arithmetic; MariaDB regression verifies large balance credit |
| Runtime/migrations | MySQL-only migration inspection, queue retry timing, incorrect test entry point | Portable migration checks; retry_after 180 seconds; Composer test runs PHPUnit directly |

Provider credentials/configuration are hidden from normal model serialization, fake local virtual accounts are blocked in production, and the merchant documentation now uses MGS headers and the current origin.

## Verification

| Check | Result |
| --- | --- |
| PHPUnit on MariaDB 10.11 | 47 passed, 165 assertions |
| PHPUnit on SQLite in memory | 46 passed, 164 assertions; one large-decimal storage test intentionally skipped |
| Two simultaneous payout processes on MariaDB | Passed: exactly one accepted, one limited; available 900.00, reserved 100.00 |
| Vite production build | Passed |
| Laravel route registration | Passed |

The MariaDB tests exercised a clean schema migration. Provider HTTP calls were mocked; no real payment, provider account, or production database was used. Build success does not establish browser usability. Evidence and the exact changed-file list are included under docs/review/ in the archive.

## Remaining work before launch

| Priority | Outstanding item | Concrete acceptance condition |
| --- | --- | --- |
| P0 | Provider contracts and certification | Obtain contracted sandbox specifications and credentials. Verify each provider's auth, payloads, bank resolution, collection signatures/status, payout references and reconciliation against real sandbox fixtures. PalmPay, OPay and 9PSB currently inherit a generic adapter; their default paths are not certified. Monnify also requires external validation. |
| P0 | Production state/data review | Audit existing processing payouts, uncertain idempotency records, wallet ledger/reservations, verified bank flags and legacy administrator references. Reconcile against provider statements before resuming traffic; never bulk-release unknown payouts. |
| P0 | Isolated merchant sandbox | Separate test wallets, customer/account records, provider configuration and events from live data. Test API keys deliberately return 503 until isolation exists. Do not simply remove this guard. |
| P0 | Blueprint pricing | Confirm the exact amount boundaries and fee ownership for the five ₦5/₦10/₦25/₦50/₦100 tiers. Connect the approved pricing model to collection credits and merchant reporting. Current FundingChargeService uses provider fixed/percentage charges; pricing_rules is not the governing calculation. Add boundary, cap, deduction/add-on and refund tests. |
| P0 | Decimal amounts through the full financial pipeline | Wallet arithmetic is fixed, but FundingChargeService and ProviderFundingCharge still use floating-point calculations; other amount-handling paths need normalization and explicit supported limits. Complete decimal/integer-kobo calculation end to end before claiming arbitrary large-value precision. |
| P0 | KYC provider verification | Configure and validate the HTTP KYC adapter against the contracted NIN/BVN provider, response mapping, duplicate policy, retries, document access and administrator review. A generic configurable adapter is not proof of successful identity verification. |
| P0 | Operational recovery | Exercise queue failure, crash after provider acceptance, delayed/out-of-order/duplicate webhooks, reconciliation exhaustion and outbox recovery on the deployed cache/queue topology. Verify shared cache atomicity and scheduler ownership. |
| P1 | PayVessel scope | PayVessel is absent from the current driver map. Confirm whether it is required by the final product scope, then implement and certify it if required. |
| P1 | Dashboard and business acceptance | Run real browser checks for registration, OTP delivery, activation, multiple businesses, contacts, support, API keys, KYC, accounts, collection, withdrawal approval and statements on mobile and desktop. Personal identity editing currently goes through support; there is no self-service reviewed-change workflow. |
| P1 | Security/operations release review | Verify upload authorization, secret access, administrator privileges, logging/redaction, retention, backups/restores, alerting, reconciliation reports and dependency vulnerability results in staging. These have not received an exhaustive penetration test or current vulnerability audit here. |

The webhook guard conservatively rejects IPv6 destinations beginning with 2001: and 2002:, so legitimate IPv6-only endpoints in those ranges may need an explicitly tested policy refinement.

## Upgrade and deployment

1. Keep this release in staging until P0 acceptance conditions pass. Back up the existing database and private uploads. Preserve the existing APP_KEY; generating another key on an existing installation can break encrypted data.
2. Install the locked dependencies with PHP 8.3 or later: `composer install` in staging (dev dependencies needed for tests), then `npm ci` and `npm run build`. For a production release use `composer install --no-dev --optimize-autoloader` after staging validation.
3. Configure a private environment from .env.example. Supply database, Redis, mail and verified provider/KYC settings. Install required PHP extensions, including curl, mbstring, XML, PDO MySQL and Redis if using phpredis. Set APP_DEBUG=false in production. Do not run blanket seeders on an existing production database.
4. Stop incoming work and pause/drain workers before migration, preserving unknown payout reservations. Run `php artisan optimize:clear`, `php artisan migrate --force`, then rebuild configuration/routes and restart workers under the deployment supervisor. New migration: 2026_10_03_000001_add_admin_actor_references.php. Historic ambiguous user/admin actor IDs are retained rather than guessed.
5. Existing environment values override defaults: explicitly set REDIS_QUEUE_RETRY_AFTER=180 (or safely longer than your longest worker/job timeout). The delivered job timeouts reach 120 seconds; keep worker timeout below retry_after. Subscribe workers to payments, webhooks, providers, notifications, kyc and default as applicable; run the scheduler every minute with shared cache.
6. Run `composer test` against disposable test data. To test MariaDB, set DB_CONNECTION=mysql, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME and DB_PASSWORD for a dedicated database. Never use production credentials with RefreshDatabase tests. The concurrent test additionally requires APP_ENV=testing and a disposable migrated database name ending in _test: `php tests/concurrency/payout_limits.php`.
7. Validate authenticated API smoke tests, isolated businesses and provider sandbox scenarios before opening traffic. Retain deployment evidence and an explicit reconciliation/rollback procedure. Financial migrations must not be reversed blindly after new transactions.

## API behavior changes to communicate

- POST /api/v1/payouts now starts the OTP withdrawal flow. Complete verification using the existing withdrawal OTP endpoints; a PIN alone is not a payout authorization.
- Merchant API keys use X-MGS-Public-Key and Authorization: Bearer <secret-key>; key ownership determines the business, not an arbitrary X-Business-ID. Dashboard sessions retain active-business selection.
- API keys cannot manage dashboard settings, secrets or payouts. Test keys are disabled pending sandbox separation.
- Optional request signing is checked whenever any signing header is present. Supply all three: X-MGS-Timestamp (Unix seconds), X-MGS-Nonce (unique value), X-MGS-Signature. Signature is `sha256=` plus HMAC-SHA256 of `timestamp.nonce.rawBody` using that key's webhook_secret. This legacy contract remains optional, uses that shared secret, and does not bind HTTP method/path; review/version it if stronger request signing is required.
- Uncertain idempotency records require operational reconciliation. Deleting them or repeating with another key can duplicate an external action.
- Existing bank records marked verified by old manual approval should be revalidated before being relied on for payouts.

## Scope of this review

This is a source and targeted regression review, with reproducible fixes and a prioritized completion plan. It cannot verify undisclosed infrastructure, provider commercial contracts, production balances or compliance approvals. The requirements blueprint planned Laravel 12; the supplied implementation declares Laravel 13.8 and PHP 8.3. Preserve that supplied dependency line unless a separately tested migration is agreed.
