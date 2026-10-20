> Current whole-project status, pending migrations and blockers: [PROJECT_STATUS.md](PROJECT_STATUS.md). This older checkpoint is historical evidence; it is not a production-readiness claim.

# MGS staging handover — 4 October 2026

This is a source handover, not authorization to process real funds. Read COMPLETION_CHECKLIST.md and the evidence in docs/review before promotion. Older review reports are historical. Blueprint V2 and contracted provider/KYC specifications were unavailable; commercial pricing and certification remain release blockers.

## Local changes and data preservation

The source checkpoint is in the system temporary directory, mgs-api-source-checkpoint-20261004/source-verified.zip. The existing database was not reset or migrated. Upgrade verification used a separate schema copy, migration bookkeeping and synthetic fixtures only. Uploaded files and .env were preserved. The administrator MFA and legacy webhook-outbox repair migrations therefore still need to be applied after backup in staging. The outbox repair adds six missing columns and preserves existing log records; clean installs already have those columns.

The original image exists. Its Preview redirect was caused by a missing public storage link allowing the SPA route to handle the URL. DocumentCard now downloads authenticated blobs from a field-based API instead of opening public storage URLs. New documents use private local storage; existing public-disk files remain readable through the authorized endpoint. The public/storage junction was removed without deleting its target. Direct activation paths return 404. Do not restore public document access when deploying.

Admin login is /admin-panel, dashboard /admin-panel/dashboard; user login is /user/login, dashboard /user/dashboard. Known legacy URLs redirect deliberately; unknown API/file routes return 404. User and administrator tokens are selected separately. Administrator MFA enrollment tokens cannot perform ordinary administrator operations. Recovery codes are single-use; TOTP replay counters are persisted. Business approval requires completed identity verification and cannot bypass suspension.

Critical fee, withdrawal, funding and provider-request amounts use decimal arithmetic. Large amounts must be decimal strings. Reports/UI formatting and commercial configuration inputs still need boundary acceptance against the approved commercial specification. Browser tokens remain in localStorage: strict CSP, dependency hygiene and a future HttpOnly session migration remain relevant XSS controls; this is not a claim that all security risks are eliminated.

## Staging preparation

1. Restore an encrypted database backup and uploaded-file backup into an isolated staging environment. Keep the existing APP_KEY when restoring encrypted records. Use separate provider sandbox credentials, database, Redis namespaces and webhook destinations; do not point staging at production financial accounts.
2. Install PHP 8.3 or later supported by composer.lock, required PDO/database extensions, mbstring, fileinfo, OpenSSL, Redis and Node compatible with package-lock.json. Run `composer install --no-dev --prefer-dist --optimize-autoloader` and `npm ci`, then `npm run build` in the build environment. Use the locked dependencies.
3. Configure an environment from .env.example only for a new installation. For an existing installation, merge individual settings securely. Never overwrite .env or regenerate APP_KEY. Set APP_ENV=production, APP_DEBUG=false, HTTPS APP_URL, dedicated database/Redis/mail credentials, exact CORS origins, secure cookies, ADMIN_REQUIRE_MFA=true and private local filesystem storage. Session domain must match the actual deployment.
4. Take a database backup immediately before `php artisan migrate --force`. Review additive migrations first. Do not use migrate:fresh, migrate:refresh, blanket seeding or financial rollback migrations. Provision an initial administrator only with the explicit AdminSeeder and operator-supplied credentials in a new environment; remove provisioning credentials afterward.
5. Point the web server exclusively at public/. Apache must honor public/.htaccess. With Nginx, put `location ^~ /storage/activations/ { return 404; }` ahead of static serving and the Laravel try_files rule. Deny .env, dotfiles and directory listing. Keep storage/app/private and storage/app/public/activations outside all public aliases. The supplied server.php applies equivalent protection to php artisan serve for development.
6. Give the runtime write access to storage and bootstrap/cache only. Run `php artisan config:cache`, `php artisan route:cache` and `php artisan view:cache` after environment configuration. Verify error responses and private documents under the actual server configuration.
7. Run supervised queue workers for the queues named by jobs (payments, webhooks, providers, notifications, kyc and default), with worker timeout 120 seconds and retry_after at least 180. Run one scheduler tick each minute: `php artisan schedule:run`. Shared cache is required for onOneServer/overlap locks. Restart workers after a release with `php artisan queue:restart`.
8. Confirm mail delivery, user OTP/recovery, administrator MFA enrollment/recovery, permissions, business selection, approval/suspension gates, private preview/download, exact pricing boundaries and settlement name enquiry using isolated fixtures. Run the database-specific concurrency regression and deployment crash/recovery tests before permitting any real funds.

Do not use composer setup against an existing financial installation as a deployment procedure: it includes migration. The environment initializer now preserves existing .env, but the explicit reviewed sequence above is required.

## Providers and pricing

Generic adapters require explicit contracted operation endpoints in production and are not certified provider implementations. PalmPay, OPay, 9PSB, PayVessel and the configured KYC adapter need the specific contracted product's authentication, request/response, signature, idempotency, reversal and reconciliation fixtures. A generic success response or a mock is not certification. Test API keys remain disabled for financial operations until completely separate sandbox ledgers and settlement are accepted.

Monnify raw-body HMAC-SHA512 verification, documented disbursement event mapping and v2 bank name enquiry were corrected using official documentation. Sandbox unsigned callbacks remain rejected because the current wallet-credit pipeline cannot safely accept unauthenticated events. Reversals after terminal success need operator reconciliation; they must not silently re-credit a wallet.

Sources: [Monnify webhook events](https://developers.monnify.com/docs/webhooks/event-types), [webhook verification](https://developers.monnify.com/docs/webhooks), [account validation change](https://developers.monnify.com/docs/change-logs), [customer verification](https://developers.monnify.com/docs/verification-api/verifying-your-customers), [single transfers](https://developers.monnify.com/docs/disbursements/single-transfers). OPay's [offline authentication documentation](https://documentation.opayweb.com/doc/offline/authentication.html) demonstrates why the exact contracted product must be identified before configuring an adapter.

Blueprint V2 is required for exact fee tiers, thresholds, caps, charge ownership and PayVessel scope. Do not infer these from unrelated public pricing.

## Operations and recovery

Monitor queue depth/age, failed jobs, stale payouts, collection reconciliation discrepancies, webhook delivery backlog, provider errors, KYC backlog and audit events. The dashboard reports health as unmonitored until an authoritative health system exists.

For a timeout or process crash during payout submission, keep the reservation. Query the original provider with the original merchant reference; do not resend, switch provider or release funds solely because the HTTP request timed out. Compare authoritative provider statements, payout status, wallet reservation, ledger and customer notification before an operator-approved adjustment. Duplicate/terminal callbacks must not apply another debit or credit.

Use `payouts:process`, `providers:reconcile`, `webhooks:retry`, `notifications:retry` and `kyc:reconcile` through the scheduler. Inspect `queue:failed` before any deliberate queue retry. Redact personal data and secrets from incident material. Preserve webhook/outbox and reconciliation records until the incident is resolved.

On release failure, stop new payment acceptance and workers, preserve evidence and database state, and revert application artifacts only if compatible with the additive schema. Never restore an old financial database over newer transactions. Restore backups first to a separate recovery environment and reconcile provider statements before any approved cutover. Encrypt database/upload backups and test restoration periodically; retain APP_KEY in a secret manager separately from source archives.

## Release blockers

- Missing Blueprint V2 and contracted provider/KYC specifications and certification fixtures.
- Real sandbox acceptance, deployment topology, mail, queue/scheduler crash recovery and TLS/CSP validation.
- Pricing/configuration boundary review, full UI journey acceptance and token-storage security decision.
- Successful final tests/audits must be read from current evidence; incomplete checks are not passes.

The clean source ZIP excludes .env, database files, uploaded documents, dependencies, logs and runtime cache. It includes sanitized .env.example, locked dependencies, source, tests, build artifacts and this handover.
