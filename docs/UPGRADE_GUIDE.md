# Upgrade Guide

## Before upgrading

1. Review the release notes and migrations.
2. Back up the database and user-uploaded files.
3. Record the current commit or release archive checksum.
4. Confirm queue backlog is controlled and no payout reconciliation is in progress.

## Upgrade procedure

```bash
php artisan down --retry=60
composer install --no-dev
composer dump-autoload -o
npm ci
npm run build
php artisan optimize:clear
php artisan migrate --force
php artisan optimize
php artisan queue:restart
php artisan up
```

Run `composer test` with development dependencies against a disposable database in staging. Do not run RefreshDatabase tests against production. See REVIEW_AND_COMPLETION_PLAN.md for this release’s migration and reconciliation requirements.

## Post-upgrade checks

- Login, registration, and authentication recovery.
- Active-business switching.
- Wallet and ledger balances for two businesses.
- API-key authentication per business.
- Settlement-account isolation.
- Virtual-account creation and collection webhook processing.
- Payout creation and processing.
- Queue and scheduler health.
- Provider health and reconciliation commands.
