<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
// Unsaved fixture exercises the real legacy file reader without any database writes.
$activation = new \App\Models\BusinessActivation(['proof_of_address' => 'activations/proof-address/ebyPzn0qgx7p425L5tDpoaBVu9K8Ntm8I9HkRlg5.jpg']);
$response = app(\App\Services\ActivationDocumentService::class)->serve($activation, 'proof_of_address');
$hash = hash_file('sha256', $response->getFile()->getPathname());
if ($response->getStatusCode() !== 200 || $response->headers->get('Content-Type') !== 'image/jpeg'
    || $hash !== '85d6038c7998b656e39e0acc5b1c721db9a20e06e0b70b78423cf29d1411ca9e') {
    throw new RuntimeException('Legacy document verification failed.');
}
$evidence = ['result' => 'passed', 'status' => 200, 'mime' => 'image/jpeg', 'bytes' => $response->getFile()->getSize(), 'hash_matches_original' => true, 'database_writes' => false];
file_put_contents(__DIR__.'/../docs/review/preserved-document.json', json_encode($evidence, JSON_PRETTY_PRINT));
echo json_encode($evidence).PHP_EOL;
