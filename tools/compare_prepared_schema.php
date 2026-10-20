<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$database = getenv('DB_DATABASE');
if (getenv('APP_ENV') !== 'testing' || ! preg_match('/^mgs_upgrade_\d{8}_\d{6}_test$/', (string) $database)) throw new RuntimeException('Disposable upgraded schema only.');
config(['database.connections.schema_reference' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true]]);
\Illuminate\Support\Facades\Artisan::call('migrate', ['--database' => 'schema_reference', '--force' => true]);
$missing = [];
$reference = \Illuminate\Support\Facades\Schema::connection('schema_reference');
$upgraded = \Illuminate\Support\Facades\Schema::connection('mysql');
foreach ($reference->getTables() as $table) {
    $name = $table['name'];
    if ($name === 'sqlite_sequence') continue;
    $columns = $upgraded->hasTable($name) ? $upgraded->getColumnListing($name) : [];
    $difference = array_values(array_diff($reference->getColumnListing($name), $columns));
    if ($difference) $missing[$name] = $difference;
}
echo json_encode(['missing_columns' => $missing], JSON_PRETTY_PRINT).PHP_EOL;
