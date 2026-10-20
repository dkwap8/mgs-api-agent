# Deployment Guide

## Requirements

- Supported PHP version matching `composer.json`.
- Composer 2.
- Node.js and npm compatible with `package-lock.json`.
- MySQL or MariaDB.
- Redis for cache, sessions, queues, locks, and scheduler single-server coordination.
- A process supervisor such as systemd or Supervisor.
- A web server configured to serve the Laravel `public` directory.

## Installation

```bash
git checkout <release>
cp .env.example .env
composer install --no-dev
composer dump-autoload -o
php artisan key:generate
npm ci
npm run build
php artisan optimize:clear
php artisan migrate --force
php artisan optimize
```

Configure writable permissions for `storage` and `bootstrap/cache`.

## Queue workers

Run separate or grouped workers for the queues used by the application:

```bash
php artisan queue:work redis --queue=payments,webhooks,providers,notifications,kyc,default --sleep=1 --tries=3 --timeout=120 --max-time=3600
```

Use Supervisor or systemd and restart workers after each deployment:

```bash
php artisan queue:restart
```

## Scheduler

Add one cron entry:

```cron
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

Verify:

```bash
php artisan schedule:list
```

## Release validation

Run:

```bash
php artisan test
```

Then complete the end-to-end scenarios in `RELEASE_NOTES.md` before enabling public traffic.
