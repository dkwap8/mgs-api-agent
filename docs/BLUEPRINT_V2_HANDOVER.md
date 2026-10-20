# Blueprint V2 PowerShell handover ? 5 October 2026

All changes are source-only and **unverified**. Run these steps in order and send the output. No dependency changed, so installation is not required for this set when existing vendor/node_modules are available. Laravel 13 remains the approved stack. No reset, key generation, destructive migration or provider transaction command is part of this handover.

## 1. Source syntax, isolated regressions and frontend build

These cover the pricing/revenue/API set, bank/payout/event/security set, and dashboard/operations set together.

```powershell
Set-Location 'C:\laragon\www\mgs-api'
php --version
$sourceFiles = Get-ChildItem -Path app,bootstrap,config,routes,database/migrations,database/seeders,tests -Filter '*.php' -Recurse
foreach ($sourceFile in $sourceFiles) {
    php -l $sourceFile.FullName
    if ($LASTEXITCODE -ne 0) { throw "PHP syntax failed: $($sourceFile.FullName)" }
}
php vendor/bin/phpunit -c phpunit.blueprint.xml --log-junit docs/review/blueprint-v2-user-tests.xml
if ($LASTEXITCODE -ne 0) { throw 'Stop: isolated tests failed. Send the output before continuing.' }
npm run build
if ($LASTEXITCODE -ne 0) { throw 'Stop: frontend build failed. Send the output before continuing.' }
```

Expected: PHP meets the installed dependencies' requirements (at least 8.3), all source syntax checks succeed, PHPUnit has no failures/errors, and Vite writes a production bundle. The Blueprint PHPUnit config forces SQLite `:memory:`, array mail/cache/session, testing mode and a separate nonexistent config-cache path. The existing TestCase rejects an operational database. This suite rebuilds only the in-memory test database and never uses your live database. Some existing database-specific cases can skip on SQLite; this does not replace a restored MariaDB upgrade/concurrency check. Tests prevent stray outbound HTTP, fake payment queues and create only synthetic fixtures. They do not certify external providers or actual email delivery.

Do not use plain `php artisan test`, Composer's setup script, `migrate:fresh`, `migrate:refresh`, `db:wipe`, a full DatabaseSeeder or `key:generate` against your current environment.

## 2. Preserve environment/uploads and create a consistent database backup

Do this before any migration. Keep the backup in your user profile, away from web-served paths. It includes a private copy of `.env` (therefore APP_KEY); never send that file or its contents in chat.

```powershell
$backupRoot = Join-Path $env:LOCALAPPDATA ('MGS-Backups\Blueprint-V2-' + (Get-Date -Format 'yyyyMMdd-HHmmss'))
New-Item -ItemType Directory -Path $backupRoot -Force | Out-Null
$environmentHashBefore = (Get-FileHash -LiteralPath '.env' -Algorithm SHA256).Hash
Copy-Item -LiteralPath '.env' -Destination (Join-Path $backupRoot 'environment.env')
$uploadRoots = @('storage\app\public','storage\app\private','public\uploads') | Where-Object { Test-Path -LiteralPath $_ }
foreach ($uploadRoot in $uploadRoots) {
    $backupLabel = $uploadRoot.Replace('\','-')
    Copy-Item -LiteralPath $uploadRoot -Destination (Join-Path $backupRoot $backupLabel) -Recurse
}
Get-Command mariadb-dump,mysqldump -ErrorAction SilentlyContinue | Select-Object Name,Source
```

If neither dump binary is on PATH, locate it:

```powershell
rg --files 'C:\laragon\bin' -g mariadb-dump.exe -g mysqldump.exe
```

For MariaDB/MySQL, use the compatible dump binary found above. Replace the example path below with the actual path. SQLite snapshots do not need this environment variable.

```powershell
$previousDumpBinary = $env:DATABASE_BACKUP_BINARY
try {
    $env:DATABASE_BACKUP_BINARY = 'C:\laragon\bin\mysql\YOUR_INSTALLED_VERSION\bin\mariadb-dump.exe'
    php artisan app:backup-database
    if ($LASTEXITCODE -ne 0) { throw 'Stop: database snapshot failed; do not migrate.' }
} finally {
    $env:DATABASE_BACKUP_BINARY = $previousDumpBinary
}
$databaseSnapshot = Get-ChildItem -LiteralPath 'storage\app\private\backups' -File |
    Where-Object { $_.Name -match '^database-.*\.(sql|sqlite)$' } |
    Sort-Object LastWriteTime -Descending | Select-Object -First 1
if (-not $databaseSnapshot) { throw 'Stop: no completed database snapshot exists.' }
Copy-Item -LiteralPath $databaseSnapshot.FullName -Destination $backupRoot
Get-FileHash -LiteralPath $databaseSnapshot.FullName -Algorithm SHA256
if ((Get-FileHash -LiteralPath '.env' -Algorithm SHA256).Hash -ne $environmentHashBefore) { throw 'Stop: environment changed.' }
```

Expected: `Private backup created` and SHA256 output, a nonempty `.sql`/`.sqlite` snapshot, private environment/upload copies and unchanged `.env` hash. The DB command uses a consistent SQLite VACUUM snapshot or a transactional MariaDB dump; it does not change balances/schema or delete old snapshots. The dump password is passed only to the child process environment. Windows backup directory access inherits your account/server permissions; restrict production access. A local file plus hash is not proven disaster recovery: restore this snapshot to a **different disposable staging database**, compare records/uploads and preserve APP_KEY there before accepting it. Database privileges, dump binary and actual restore output remain your responsibility. For nontransactional database tables, select a DBA-approved locking/backup procedure rather than relying on `--single-transaction`.

## 3. Review/apply additive migrations in restored staging

Run this in the isolated restored staging checkout/environment first, never a test command pointed at the original DB. Do not replace the original `.env` with staging settings. New source requires new columns before its affected routes can be used.

```powershell
php artisan migrate:status
php artisan migrate --pretend
```

Expected: the prior pending MFA/outbox migrations, if any, plus these three new migrations are listed. Review the SQL; there should be no table drop, truncation, key reset or financial-row delete in this set:

- `2026_10_05_000001_create_revenues_and_scope_api_logs`: new unique transaction-linked fee table; nullable business reference/index for request logs.
- `2026_10_05_000002_add_api_secret_lookup_and_bank_delay`: nullable hashed secret lookup and bank hold timestamps; single-use bank confirmation records.
- `2026_10_05_000003_allow_kyc_processing_status`: widen KYC status so asynchronous processing is a permitted value. Forward-only retention is deliberate; do not assume rollback deletes these additions.

After the backup/restore and SQL review are accepted, the operator applies them:

```powershell
php artisan migrate
if ($LASTEXITCODE -ne 0) { throw 'Stop: migration failed. Send the output.' }
php artisan db:seed --class=BlueprintPricingSeeder
if ($LASTEXITCODE -ne 0) { throw 'Stop: Blueprint draft seed failed.' }
php artisan db:seed --class=PermissionSeeder
if ($LASTEXITCODE -ne 0) { throw 'Stop: role permission seed failed.' }
php artisan optimize:clear
php artisan route:list --path=api/v1
php artisan schedule:list
```

Expected: additive migrations complete; exactly five Blueprint collection drafts exist and remain inactive (repeat seeding preserves existing draft edits/activation). PermissionSeeder grants finance reports.view and keeps provider write restricted; **it syncs built-in role permissions**, so review customized roles before running it and manually apply reports.view instead if preserving a custom built-in mapping. Optimization clear removes stale caches, not records. Route list includes customer verification, payment verification, merchant dashboard/logs/notifications/bank challenge, administrator pricing/VA/KYC/audit/webhook routes. Scheduler includes daily DB snapshot and five-minute application health checks alongside existing retries/reconciliation. Daily backup uses the configured `DATABASE_BACKUP_BINARY`; configuring the deployed scheduler is a separate operations step. Future optimization/cache builds remain operator-owned.

After staging is accepted, repeat the reviewed backup/migration procedure in the original deployment if you choose to install this set there. Do not infer approval or verification from this document.

## 4. Application health and browser acceptance after migration/build

```powershell
php artisan app:check-system-health
```

Expected: JSON with checked_at, database availability, storage headroom, recent API error counts, PHP process memory and a truthful `healthy` result. A failure exit can mean low disk or error threshold breach; send the JSON for review. Host CPU/RAM are explicitly unmonitored by this application check. Recent results appear in the administrator summary. No provider health or financial calls are executed by this command.

Open your configured site in a browser and send results for:

1. Merchant API Logs: own business only, metadata filters and pagination, no credentials/body/other-business records.
2. Merchant notification bell: actual stored notices, empty/error state, mark-read ownership. Login/key/bank/payout notices queue email; SMTP acceptance requires configured mail and a supervised notifications queue.
3. Merchant dashboard: complete period totals and success rate, today net collections, correct API access status; pending businesses can read onboarding without restricted customer writes.
4. Bank Accounts: password-confirmed email code, wrong/expired/reused/different-destination code rejection, verified name from provider, 24-hour hold, no fund reservation while held. Actual name enquiry needs provider credentials; local regressions use fixtures.
5. Administrator Virtual Accounts, KYC Requests, Audit Logs, Webhook Deliveries: permission enforcement, search/filter/page, redacted identity/provider data. KYC notes/retry never manufacture a verified result.
6. Pricing: inactive exact drafts; overlap rejection; approved rule activation only after decimal-gap/low-amount decision. No active-rule match stops collection processing instead of guessing a fee.
7. Request auth: newly rotated keys use bearer-only; legacy keys retain public header; both HMAC schemes work with fresh signatures/replay rejection. Rotating keys revokes old keys and needs deliberate integration updates.
8. Terminal callbacks: duplicate success gives one credit/revenue/outbox; failure gives no credit; contradictory failed callback/success verification is held for reconciliation. Test accounts cannot enter live collection processing; provider-specific reversal rules need contracts.
9. Maintenance: merchant API returns 503 with Retry-After; staff recovery endpoints and signed provider callback ingestion remain available.

No sandbox/live transaction, full-server backup, cloud copy, SMS delivery, key rotation, commercial activation or queue processing was performed here. Device/IP risk holds currently force manual review for new/untrusted devices; automatic trust promotion requires an approved policy. Legacy unscoped logs/config/identity data are retained, with only new config writes encrypted and verified transient identities cleared. Revenue records are created for newly settled collections; no historical financial backfill or invented provider costs was applied.

Send command output and browser results before any claim of verification. The exact remaining provider/commercial/deployment inputs are in BLUEPRINT_V2_REQUIREMENT_MATRIX.md.
