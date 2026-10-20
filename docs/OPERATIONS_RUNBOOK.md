# Operations Runbook

## Routine health checks

```bash
php artisan about
php artisan schedule:list
php artisan queue:monitor payments,webhooks,providers,notifications,kyc,default --max=100
php artisan migrate:status
```

Check application logs, failed jobs, Redis connectivity, database connections, provider health, webhook backlog, pending payouts, KYC retries, and notification failures.

## Queue operations

Restart after deployments or configuration changes:

```bash
php artisan queue:restart
```

Inspect failures:

```bash
php artisan queue:failed
php artisan queue:retry <id>
```

Do not retry financial jobs blindly. Verify idempotency keys, provider references, wallet ledger state, and payout status first.

## Scheduled operations

The scheduler handles payout processing, webhook retries, provider health and reconciliation, provider priority optimisation, provider cleanup, notification retries, idempotency pruning, expired authentication cleanup, and KYC reconciliation.

## Financial incident response

1. Put affected provider or operation into a safe disabled state.
2. Preserve request IDs, provider references, idempotency keys, webhook payloads, and ledger entries.
3. Do not manually change wallet balances without a compensating ledger entry and authorised audit record.
4. Reconcile provider status before retrying payouts or collections.
5. Confirm merchant and business ownership on every affected record.

## Business isolation check

For suspected cross-business exposure, compare `merchant_id`, `business_id`, wallet ID, API-key business, settlement account business, transaction business, payout business, cache namespace, and the incoming `X-Business-ID` header.

## Maintenance mode

```bash
php artisan down --retry=60
# perform maintenance
php artisan up
```
