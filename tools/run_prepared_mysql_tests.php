<?php
// Run regressions on the schema-copy upgrade fixture without repeating slow DDL.
// This clears synthetic data in that disposable copy, never an operational DB.
$database = getenv('DB_DATABASE');
if (getenv('APP_ENV') !== 'testing' || getenv('DB_CONNECTION') !== 'mysql'
    || ! is_string($database) || ! preg_match('/^mgs_upgrade_\d{8}_\d{6}_test$/', $database)) {
    throw new RuntimeException('Use only the disposable mgs_upgrade schema-copy fixture.');
}
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$connection = \Illuminate\Support\Facades\DB::connection();
if ($connection->getDatabaseName() !== $database
    || ! \Illuminate\Support\Facades\Schema::hasColumn('admins', 'two_factor_secret')) {
    throw new RuntimeException('Prepared upgraded schema was not selected.');
}
$connection->statement('SET FOREIGN_KEY_CHECKS=0');
try {
    foreach ($connection->select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'") as $row) {
        $table = (string) array_values((array) $row)[0];
        if ($table === 'migrations') continue;
        if (! preg_match('/^[a-zA-Z0-9_]+$/', $table)) throw new RuntimeException('Invalid fixture table.');
        // DELETE avoids another DDL cycle; every record here is synthetic.
        $connection->table($table)->delete();
    }
} finally {
    $connection->statement('SET FOREIGN_KEY_CHECKS=1');
}
\Illuminate\Foundation\Testing\RefreshDatabaseState::$migrated = true;
$connection->disconnect();
\Illuminate\Support\Facades\Facade::clearResolvedInstances();
\Illuminate\Support\Facades\Facade::setFacadeApplication(null);
$app->flush();
\Illuminate\Container\Container::setInstance(null);
restore_error_handler();
restore_exception_handler();
exit((new \PHPUnit\TextUI\Application)->run(array_merge(['phpunit'], array_slice($argv, 1))));
