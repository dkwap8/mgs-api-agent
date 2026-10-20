<?php

// Existing environments and encryption keys must never be replaced by setup.
$root = dirname(__DIR__);
if (file_exists($root.'/.env')) {
    echo "Existing .env preserved. Configure missing settings explicitly.\n";
    exit(0);
}
$example = file_get_contents($root.'/.env.example');
$example = preg_replace('/^APP_KEY=.*$/m', 'APP_KEY=base64:'.base64_encode(random_bytes(32)), $example);
$handle = fopen($root.'/.env', 'x');
if ($handle === false) {
    throw new RuntimeException('Unable to create environment file safely.');
}
fwrite($handle, $example);
fclose($handle);
echo "Created a new environment. Configure database and provider credentials before migration.\n";
