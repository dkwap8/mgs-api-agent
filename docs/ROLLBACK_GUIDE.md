# Rollback Guide

## Principles

Application rollback and database rollback must be planned separately. Financial migrations may contain backfills or constraints that should not be reversed until their operational impact is understood.

## Application rollback

```bash
php artisan down --retry=60
git checkout <previous-release>
composer install --no-dev
composer dump-autoload -o
npm ci
npm run build
php artisan optimize:clear
php artisan optimize
php artisan queue:restart
php artisan up
```

## Database rollback

Prefer restoring a verified pre-deployment database backup for a full release rollback. Use `php artisan migrate:rollback` only after reviewing the exact migrations and confirming that rollback will not discard financial ownership, ledger, KYC, payout, or provider data.

## Incident preservation

Before rollback, preserve application logs, failed jobs, request IDs, idempotency keys, webhook payloads, provider references, payout attempts, and database snapshots.

## Verification after rollback

Confirm authentication, business switching, wallet balances, transaction history, settlement accounts, API keys, queues, scheduler, webhooks, and provider connectivity.
