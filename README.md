# MGS API payment gateway

Laravel and Vue fintech application with business-scoped wallets, accounts, collections and payouts.

## Release status — 4 October 2026

This checkout contains local security, authorization, document-preview, monetary arithmetic and upgrade fixes. Current verification is recorded in [Completion checklist](docs/COMPLETION_CHECKLIST.md) and docs/review. Provider HTTP was mocked. Production acceptance remains blocked by provider certification, isolated sandbox, Blueprint V2 pricing and deployment acceptance.

Read [Staging handover](docs/STAGING_HANDOVER.md) for upgrade, deployment, provider blockers and recovery instructions. The earlier [Review and completion plan](docs/REVIEW_AND_COMPLETION_PLAN.md) is historical. Run tests using `composer test` or `vendor/bin/phpunit` with dev dependencies installed, only against disposable data.

The package excludes .env, dependencies, databases, private uploads, logs and runtime caches. Install dependencies from the supplied lockfiles. Existing operational documents are background references; the completion checklist and staging handover govern this checkout's verified status and remaining work.

Current completion work and evidence are tracked in [Completion checklist](docs/COMPLETION_CHECKLIST.md). Browser login paths are `/admin-panel` and `/user/login`; dashboards are `/admin-panel/dashboard` and `/user/dashboard`.

Activation previews and downloads require authenticated API access. New uploads use `storage/app/private`; legacy `storage/app/public/activations` files are read only through the authorized endpoints. Do not expose legacy activation documents via a public storage link. See the current handover for web-server restrictions and the administrator MFA migration.
