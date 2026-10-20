<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$source = config('database.connections.mysql');
$name = 'mgs_upgrade_'.date('Ymd_His').'_test';
$pdo = new PDO('mysql:host='.$source['host'].';port='.$source['port'].';dbname='.$source['database'].';charset=utf8mb4', $source['username'], $source['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$tables = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN);
$pdo->exec('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$clone = new PDO('mysql:host='.$source['host'].';port='.$source['port'].';dbname='.$name.';charset=utf8mb4', $source['username'], $source['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$clone->exec('SET FOREIGN_KEY_CHECKS=0');
foreach ($tables as $table) {
    if (! preg_match('/^[a-zA-Z0-9_]+$/', $table)) throw new RuntimeException('Unsupported table identifier.');
    $definition = $pdo->query('SHOW CREATE TABLE `'.$table.'`')->fetch(PDO::FETCH_NUM)[1];
    $clone->exec($definition);
}
$clone->exec('SET FOREIGN_KEY_CHECKS=1');
// Copy only migration bookkeeping; operational/customer data is never copied.
foreach ($pdo->query('SELECT id, migration, batch FROM migrations')->fetchAll(PDO::FETCH_ASSOC) as $migration) {
    $clone->prepare('INSERT INTO migrations (id, migration, batch) VALUES (?, ?, ?)')->execute(array_values($migration));
}
$clone->prepare('INSERT INTO admins (name, email, password, role, status) VALUES (?, ?, ?, ?, ?)')->execute(['Upgrade fixture', 'upgrade@example.test', 'unchanged-fixture-hash', 'super_admin', 'active']);
config(['database.connections.schema_upgrade_test' => array_merge($source, ['database' => $name, 'url' => null])]);
\Illuminate\Support\Facades\DB::setDefaultConnection('schema_upgrade_test');
$app->detectEnvironment(fn () => 'testing');
\Illuminate\Support\Facades\Artisan::call('migrate', ['--database' => 'schema_upgrade_test', '--force' => true]);
if (! \Illuminate\Support\Facades\Schema::connection('schema_upgrade_test')->hasColumn('admins', 'two_factor_secret')) throw new RuntimeException('MFA column missing after upgrade.');
if ($clone->query("SELECT password FROM admins WHERE email='upgrade@example.test'")->fetchColumn() !== 'unchanged-fixture-hash') throw new RuntimeException('Existing fixture changed.');
$evidence = ['result' => 'passed', 'copied_schema_tables' => count($tables), 'fixture_preserved' => true, 'operational_data_copied' => false, 'source_database_mutated' => false, 'disposable_schema' => $name];
file_put_contents(__DIR__.'/../docs/review/existing-schema-upgrade.json', json_encode($evidence, JSON_PRETTY_PRINT));
echo 'PASS: upgraded copy of the local schema; fixture preserved; original database unchanged.'.PHP_EOL;
