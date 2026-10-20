<?php
if (getenv('APP_ENV') !== 'testing' || getenv('DB_DATABASE') !== 'mgs_upgrade_20261004_175039_test') throw new RuntimeException('Disposable fixture only.');
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (\Illuminate\Support\Facades\DB::connection()->getDatabaseName() !== getenv('DB_DATABASE')) throw new RuntimeException('Wrong database.');
\Illuminate\Support\Facades\Schema::table('users', function ($table) { $table->text('two_factor_recovery_codes')->nullable()->change(); });
