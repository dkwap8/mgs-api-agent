<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$c = config('database.connections.mysql');
$pdo = new PDO('mysql:host='.$c['host'].';port='.$c['port'], $c['username'], $c['password']);
foreach ($pdo->query('SHOW PROCESSLIST')->fetchAll(PDO::FETCH_ASSOC) as $row) {
    if (str_ends_with((string) $row['db'], '_test')) echo json_encode(array_intersect_key($row, array_flip(['Id', 'db', 'Command', 'Time', 'State']))).PHP_EOL;
}
