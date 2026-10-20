<?php
// Run only against a disposable, migrated MariaDB database whose name ends in _test.
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\{DB, Hash, Event, Queue};
use App\Models\{User, Merchant, Business, BusinessActivation, BankAccount, MerchantWithdrawalSetting, WithdrawalOtp, Payout, Wallet};
use App\Services\{WalletService, PayoutService};
if (! app()->environment('testing') || DB::getDriverName() !== 'mysql' || ! str_ends_with(DB::getDatabaseName(), '_test')) {
    throw new RuntimeException('Use APP_ENV=testing and a disposable MySQL/MariaDB database ending in _test.');
}
config(['app.key' => 'base64:'.base64_encode(str_repeat('t',32)), 'cache.default' => 'array', 'queue.default' => 'null']);
Event::fake();
Queue::fake();
if (($argv[1] ?? '') === 'worker') {
    [$merchantId, $businessId, $bankId, $otpId, $readyFile] = array_slice($argv, 2);
    file_put_contents($readyFile, 'ready');
    try {
        app(PayoutService::class)->requestPayout((int) $merchantId, (int) $businessId, (int) $bankId, 100, null, ['withdrawal_otp_request_id' => $otpId]);
        echo 'accepted';
    } catch (Illuminate\Validation\ValidationException $e) {
        echo 'limited';
    }
    exit;
}
$tag = bin2hex(random_bytes(5));
$user = User::create(['uuid' => (string) Illuminate\Support\Str::uuid(), 'name' => 'Concurrency Test', 'email' => $tag.'@example.test', 'password' => Hash::make('test-password'), 'status' => 'active']);
$merchant = Merchant::create(['user_id' => $user->id, 'business_name' => 'Concurrency Test', 'verification_status' => 'approved', 'is_verified' => true]);
$business = Business::create(['merchant_id' => $merchant->id, 'name' => 'Test', 'business_code' => $tag, 'verification_status' => 'approved', 'is_verified' => true]);
BusinessActivation::create(['merchant_id' => $merchant->id, 'business_id' => $business->id, 'user_id' => $user->id, 'business_name' => 'Test', 'business_email' => $tag.'@example.test', 'business_phone' => '08000000000', 'business_description' => 'Test', 'state' => 'Kano', 'city' => 'Kano', 'street_address' => 'Test', 'proof_of_address' => 'test.pdf', 'bank_name' => 'Test', 'account_number' => '1234567890', 'account_name' => 'Test', 'business_type' => 'starter', 'status' => 'approved']);
$bank = BankAccount::create(['merchant_id' => $merchant->id, 'business_id' => $business->id, 'bank_name' => 'Test', 'bank_code' => '999', 'account_number' => '1234567890', 'account_name' => 'Test', 'is_verified' => true, 'status' => 'active']);
MerchantWithdrawalSetting::updateOrCreate(['merchant_type' => 'starter'], ['withdrawals_enabled' => true, 'automatic_withdrawals_enabled' => false, 'minimum_withdrawal_amount' => 1, 'daily_withdrawal_limit' => 150]);
$wallet = app(WalletService::class)->createWallet($merchant->id, $business->id);
app(WalletService::class)->credit($merchant->id, $business->id, 1000, 'test', 1);
$otps = [];
for ($i = 0; $i < 2; $i++) {
    $otps[] = WithdrawalOtp::create(['request_id' => (string) Illuminate\Support\Str::uuid(), 'user_id' => $user->id, 'merchant_id' => $merchant->id, 'business_id' => $business->id, 'bank_account_id' => $bank->id, 'amount' => 100, 'otp_hash' => Hash::make('123456'), 'status' => 'processing', 'verified_at' => now(), 'last_sent_at' => now(), 'expires_at' => now()->addMinutes(5)]);
}
DB::beginTransaction();
Wallet::whereKey($wallet->id)->lockForUpdate()->firstOrFail();
$processes = [];
foreach ($otps as $i => $otp) {
    $ready = sys_get_temp_dir().'/mgs-concurrency-'.$tag.'-'.$i;
    $pipes = [];
    $process = proc_open([PHP_BINARY, __FILE__, 'worker', (string) $merchant->id, (string) $business->id, (string) $bank->id, $otp->request_id, $ready], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (! is_resource($process)) throw new RuntimeException('Unable to start worker');
    $processes[] = [$process, $pipes, $ready];
}
$deadline = microtime(true) + 10;
while (microtime(true) < $deadline && (! file_exists($processes[0][2]) || ! file_exists($processes[1][2]))) usleep(10000);
if (! file_exists($processes[0][2]) || ! file_exists($processes[1][2])) throw new RuntimeException('Workers failed to start');
usleep(100000);
DB::commit();
$results = [];
foreach ($processes as [$process, $pipes, $ready]) {
    $results[] = trim(stream_get_contents($pipes[1]));
    $error = stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    if (proc_close($process) !== 0) throw new RuntimeException($error);
    unlink($ready);
}
sort($results);
$wallet->refresh();
if ($results !== ['accepted', 'limited'] || Payout::where('business_id', $business->id)->count() !== 1 || $wallet->available_balance !== '900.00' || $wallet->reserved_balance !== '100.00') {
    throw new RuntimeException('Concurrent withdrawal invariant failed: '.json_encode($results));
}
echo "PASS: two simultaneous requests; exactly one accepted; daily limit and wallet reservation preserved.\n";
