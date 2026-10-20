# MGS payment gateway status ? 4 October 2026

**Production readiness: NO.** This is a working implementation with verified local controls, unfinished product scope and unverified external integrations. A source archive or passing mocked tests is not production acceptance. This report supersedes staging handover wording; historical evidence is dated and does not automatically cover subsequent edits.

## Completed and locally verified

- Separate `/admin-panel` administrator login and `/admin-panel/dashboard`; separate `/user/login` and `/user/dashboard`. The public user login has no administrator option; query parameters cannot switch its context. Backend rejects administrator credentials/tokens as a user and user tokens as an administrator.
- Activation document Preview/Download: original JPEG exists and its SHA256 is preserved. Missing public storage link previously sent the URL into the SPA fallback. Documents now use authorized private delivery, including preserved legacy uploads. Static activation URLs are blocked. Preview layers correctly above the review dialog. Approval has a neutral confirmation rather than warning styling.
- Business ownership/active-business checks, per-business wallets and settlement accounts, approval/suspension gates, API-key business scopes. Regression tests cover isolation and unauthorized access.
- Exact critical money/fee calculations, wallet reservation, bound withdrawal OTP, withdrawal limits, duplicate settlement protection, uncertain payout reservation, reconciliation with the original provider/reference and outbox delivery controls. Previous real two-process MariaDB limit test accepted one request and rejected the other; it did not contact a payment provider.
- Administrator MFA enrollment/recovery and access restrictions; user MFA password-confirmed setup, encrypted new secrets, pending confirmation, replay prevention, single-use recovery codes, actual device listing/revocation. Password reset expiration and single consumption revoke sessions. Existing legacy plaintext user MFA secrets remain readable and are not silently rewritten.
- Browser authentication now issues separate HttpOnly SameSite=Strict administrator/user cookies, Secure under production/HTTPS, with CSRF checks. Browser JSON no longer exposes the bearer token. API clients retain bearer support. Existing browser users must sign in again; old localStorage tokens are removed. Security tests verify cookie/actor isolation and CSRF.
- Production generic provider adapters require explicitly configured contracted endpoints; default placeholder endpoints cannot satisfy that guard.
- Reports retain decimal totals and report profit as unavailable until provider costs are accounted for. Fees alone are not profit.
- SQLite migration/tests and disposable MariaDB upgrade/tests are used without migrating the original database. Current continuation evidence is under docs/review/continuation-*; earlier final-verification.json describes the earlier checkpoint.

## Implemented but still needs external/deployment verification

| Area | Implemented | Still required |
|---|---|---|
| Business isolation | Ownership and active-business financial scopes, suspension gates | Production role matrix and realistic migrated data acceptance |
| Wallets | Separate balances, exact ledger, reservation/idempotency | Opening balance reconciliation against authoritative statements, restore/recovery acceptance |
| Collections/virtual accounts | Provisioning adapters, verification, duplicate-safe credit, reconciliation | Actual provider sandbox account creation/settlement, duplicate/delayed/reversal events and provider statement reconciliation |
| Withdrawals | PIN/OTP, limits, approval, reservation, original-provider reconciliation | Sandbox beneficiary validation, payout success/failure/timeout/unknown/late outcome and worker crash acceptance |
| API keys | Business scoping, revocation, hidden secrets, production operation gates | Integrator end-to-end acceptance and isolated sandbox ledger implementation |
| Webhooks | Signature validation, replay/duplicate protection, signed merchant delivery, SSRF controls/outbox retries | Real provider signature fixtures and HTTPS delivery/retry/crash acceptance under deployment DNS/network |
| Pricing | Configurable rules and exact arithmetic | Blueprint V2 tier boundaries/caps/charge ownership and provider cost rules |
| KYC | Encrypted sensitive records, asynchronous verification/retry lifecycle and business activation gate | Contracted NIN/BVN API mapping, consent/data policy, sandbox positive/negative/duplicate/timeout cases |
| Security | Actor/permission checks, MFA, cookies/CSRF, private documents, redaction, rate limits, production headers | TLS/proxy/CSP verification on target host, penetration review, backup restoration and legacy secret encryption policy |
| Administrator dashboard | Aggregate totals, activation/payout/provider/configuration controls, reporting | Production permissions, operational health source, all primary workflows with realistic fixtures |
| User dashboard | Business-specific wallet/onboarding, financial/service/settings flows | Full approved/pending/suspended multi-business acceptance, provider-backed journeys and browser/device acceptance |
| Notifications/support | Controllers, queues, delivery/retry/audit infrastructure | Real SMTP delivery, queue/scheduler operation and complete support workflow acceptance |

## Incomplete or missing

- Blueprint V2 has not been supplied/found; complete requirements conformity cannot be established.
- PalmPay, OPay, 9PSB, PayVessel and generic KYC adapters are not certified contracted integrations. Explicit endpoints alone do not implement a provider's full protocol.
- Completely isolated financial sandbox ledger/provisioning is unfinished. Test financial keys fail closed rather than crediting operational wallets.
- Provider costs and profit accounting are incomplete; the dashboard intentionally cannot claim profit.
- Approved privacy/terms/legal content is missing; existing placeholder links need supplied policies.
- Authoritative operational health/alerts, deployment failover/crash recovery and target-host acceptance are unfinished/unverified.
- Full Blueprint-driven UI/product acceptance cannot be claimed. Mocked dashboard browser checks only verify selected journeys.

## Blockers, exact input and closure steps

1. **Blueprint:** place the document at `C:\laragon\www\mgs-api\docs\specifications\MGS_API_Platform_Blueprint_V2.pdf` (or `.md`/`.docx`). Include exact products, payment/collection fee tiers, inclusive boundaries, caps, charge payer, settlement timing, refunds/reversals, limits, roles and PayVessel scope. Then map every requirement to code/tests and implement gaps.
2. **Provider contracts:** place nonsecret contracted docs/fixtures in `private/provider-contracts/<provider>/` for `monnify`, `palmpay`, `opay`, `ninepsb`, `payvessel`, `kyc`. Need exact product/version, sandbox base URL, authentication/token expiry, VA/KYC/bank enquiry/payout/status endpoints and request/response codes, idempotency, retry rules, raw signature examples, timestamps/event IDs, callbacks, timeout/reversal behavior, statements and certification cases. Implement/verify each exact adapter before enabling it.
3. **Credentials:** configure sandbox credentials locally in a separate staging checkout/environment and database, never in chat or source. Generic provider config uses base_url/api_key/secret_key/webhook_secret; provider records have encrypted config and contract operation metadata via authenticated admin settings. Monnify additionally needs contract_code and wallet_account_number (disbursement source), plus contracted preferred_banks/get_all_available_banks settings. Enter these through /admin-panel/providers in the isolated environment, whose provider records encrypt credential config. Use only permitted sandbox credentials, never production funds. Do not assume setting generic PROVIDER_* variables configures every provider independently. KYC uses KYC_BASE_URL/KYC_API_KEY/KYC_API_SECRET/KYC_AUTH_SCHEME and contracted endpoint/response mappings listed in .env.example; defaults are placeholders until validated. Need provider-approved sandbox beneficiaries, NIN/BVN fixtures and callback registration/allowlists.
4. **Sandbox acceptance:** after isolation is implemented and credentials/docs supplied, use authorized provider sandboxes to verify positive/negative/duplicate/delayed/timeout/reconciliation cases. No actual provider sandbox tests have run here. No real payments were initiated. Obtain explicit approval for sandbox financial calls before running them.
5. **Deployment:** provide target host/domain and access/topology for HTTPS/proxy settings, Redis shared locks, supervised named queues, scheduler, database, SMTP and monitoring. Configure secrets locally with restricted access/secret manager. Validate mail, TLS/CSP/cookies, alerts, crash/restart recovery and provider callbacks there.
6. **Backup/migrations:** provide or create an encrypted consistent backup of the original database and uploads, preserve APP_KEY separately, restore to a separate database and verify schema/row counts/representative reads. Then review and authorize the four additive migrations below. No restorable original database backup has been verified; a source ZIP/schema-only test copy is not such a backup. Do not migrate the original until this is closed.
7. **Commercial/operations:** supply approved policies, costs/pricing, roles and reconciliation procedures. Finish cost accounting and sandbox isolation, then complete the requirement matrix and operator/security acceptance.

## Pending original database migrations

Read-only `php artisan migrate:status` confirmed all four remain pending; none were applied to the original database.

| Migration | Effects |
|---|---|
| 2026_10_04_000001_add_administrator_mfa | Adds nullable encrypted administrator confirmed/pending secret and recovery-code storage, confirmation timestamp and replay counter. No administrator/data deletion. |
| 2026_10_04_000002_repair_legacy_webhook_outbox_columns | Conditionally adds missing nullable transaction reference (FK null on transaction deletion), delivery URL, request headers JSON, response body, last error and delivered timestamp to webhook_logs. Preserves existing rows; rollback retains delivery evidence. |
| 2026_10_04_000003_add_user_mfa_pending_secret_and_replay_counter | Adds nullable users.two_factor_pending_secret and two_factor_last_counter; preserves existing passwords, secrets and records. Rollback retains authentication fields. |

| 2026_10_04_000004_widen_user_mfa_secret_for_encryption | Widens existing users.two_factor_secret and two_factor_recovery_codes to nullable TEXT so encrypted values fit; preserves current values without bulk encryption. Rollback never shrinks/truncates secrets. |

DDL can lock tables and needs reviewed deployment timing. MFA features require these columns: their new code is not ready against the original unmigrated schema. .env and APP_KEY were not changed. No migrate:fresh was run against the original database. Uploaded files were not deleted.

## Test interpretation

Provider payment, payout, KYC and webhook service regression tests use Http::fake or mock provider services. They prove local state transitions/assertions against fixtures, not external provider behavior. SQLite and MariaDB tests exercise actual database behavior in isolated synthetic databases. Browser checks intercept authenticated API responses with fixtures. Previous dependency audits found zero reported vulnerabilities in the locked dependencies; they are point-in-time scans, not payment certification. Actual provider sandbox calls: **zero**. Production payment verification: **zero**.

The complete project is **not production ready** until the blockers above are closed with evidence. Work completed locally is valuable, but there is no honest basis to call the entire gateway complete or safe for live funds.

## Current continuation verification

- SQLite: 79 tests; 78 passed, one database-specific skip, 316 assertions.
- Upgraded disposable MariaDB: 79 tests passed, 317 assertions. Both new encrypted MFA fields were widened in the disposable copy; the final fourth migration was also exercised through fresh SQLite migrations.
- Production frontend build passed; changed Vue components independently compiled. All ten latest browser checks passed with no page errors; results are recorded in docs/review/browser/results.json and still use mocked authenticated API data.
- Schema-copy comparison found no missing columns (not a full type/index equivalence check). Original migration status lists four pending migrations.
- Original .env and uploaded JPEG SHA256 hashes match their preservation baselines. Preserving .env also preserves its APP_KEY.
