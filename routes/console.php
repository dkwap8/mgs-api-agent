<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Foundation\Inspiring;

Artisan::command('inspire', function (): void {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:backup-database')->dailyAt('01:00')->when(fn () => (bool) config('operations.backup_enabled'))->withoutOverlapping(120)->onOneServer();
Schedule::command('app:check-system-health')->everyFiveMinutes()->withoutOverlapping(10)->onOneServer();

Schedule::command('payouts:process --limit=100 --stale=10 --max-attempts=12')
    ->everyMinute()
    ->withoutOverlapping(10)
    ->onOneServer()
    ->runInBackground();


Schedule::command('webhooks:retry --limit=200 --max-attempts=8')
    ->everyMinute()
    ->withoutOverlapping(10)
    ->onOneServer()
    ->runInBackground();

Schedule::command('providers:health')
    ->everyFiveMinutes()
    ->withoutOverlapping(10)
    ->onOneServer()
    ->runInBackground();


Schedule::command('providers:reconcile --limit=200 --stale=10')
    ->everyFiveMinutes()
    ->withoutOverlapping(15)
    ->onOneServer()
    ->runInBackground();

Schedule::command('providers:optimise-priorities --hours=24')
    ->hourly()
    ->withoutOverlapping(30)
    ->onOneServer()
    ->runInBackground();

Schedule::command('providers:cleanup --days=90')
    ->dailyAt('02:30')
    ->withoutOverlapping(60)
    ->onOneServer()
    ->runInBackground();

Schedule::command('notifications:retry --limit=200')
    ->everyMinute()
    ->withoutOverlapping(10)
    ->onOneServer()
    ->runInBackground();


Schedule::command('idempotency:prune --days=1')
    ->dailyAt('02:30')
    ->withoutOverlapping(30)
    ->onOneServer()
    ->runInBackground();

Schedule::command('auth:prune-expired')
    ->hourly()
    ->withoutOverlapping(30)
    ->onOneServer()
    ->runInBackground();


Schedule::command('kyc:reconcile --limit=200 --stale=15')
    ->everyFiveMinutes()
    ->withoutOverlapping(15)
    ->onOneServer()
    ->runInBackground();
