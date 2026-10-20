<?php

namespace App\Services;

use App\Jobs\SendWebhookJob;
use App\Models\Transaction;
use App\Models\VirtualAccount;
use App\Models\WebhookEndpoint;
use App\Models\WebhookLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class MerchantWebhookService
{
    private const MAX_ATTEMPTS = 8;

    public function sendCollection(Transaction $transaction, VirtualAccount $virtualAccount): ?WebhookLog
    {
        $endpoint = WebhookEndpoint::query()
            ->where('merchant_id', $virtualAccount->merchant_id)
            ->where('business_id', $virtualAccount->business_id)
            ->where('status', WebhookEndpoint::STATUS_ACTIVE)
            ->where('environment', WebhookEndpoint::ENVIRONMENT_LIVE)
            ->first();

        if (! $endpoint || (! $endpoint->subscribesTo('payment.success') && ! $endpoint->subscribesTo('collection.success'))) {
            return null;
        }

        $event = $endpoint->subscribesTo('payment.success') ? 'payment.success' : 'collection.success';
        $payload = [
            'event' => $event,
            'reference' => $transaction->reference,
            'provider_reference' => $transaction->provider_reference,
            'status' => $transaction->status,
            'amount' => (string) $transaction->amount,
            'fee' => (string) $transaction->fee,
            'merchant_amount' => (string) $transaction->merchant_amount,
            'currency' => 'NGN',
            'customer' => [
                'id' => $transaction->customer_id,
                'name' => $virtualAccount->customer?->fullName(),
                'email' => $virtualAccount->customer?->email,
                'phone' => $virtualAccount->customer?->phone,
            ],
            'virtual_account' => [
                'id' => $virtualAccount->id,
                'account_number' => $virtualAccount->account_number,
                'account_name' => $virtualAccount->account_name,
                'bank_name' => $virtualAccount->bank_name,
            ],
            'created_at' => $transaction->created_at?->toIso8601String(),
        ];

        return $this->queue($endpoint, $event, $payload, $transaction->id);
    }

    public function sendPaymentFailure(Transaction $transaction): ?WebhookLog
    {
        return $this->sendBusinessEvent($transaction->merchant_id, $transaction->business_id, 'payment.failed', [
            'reference'=>$transaction->reference, 'provider_reference'=>$transaction->provider_reference,
            'status'=>$transaction->status, 'amount'=>(string)$transaction->amount, 'currency'=>'NGN',
        ], $transaction->id);
    }

    public function sendPayout(\App\Models\Payout $payout): ?WebhookLog
    {
        $event = $payout->status === \App\Models\Payout::STATUS_SUCCESSFUL ? 'payout.success' : 'payout.failed';
        return $this->sendBusinessEvent($payout->merchant_id, $payout->business_id, $event, [
            'reference'=>$payout->reference, 'status'=>$payout->status, 'amount'=>(string)$payout->amount, 'currency'=>'NGN',
        ]);
    }

    public function sendVirtualAccount(VirtualAccount $account): ?WebhookLog
    {
        if ($account->is_test) return null;
        return $this->sendBusinessEvent($account->merchant_id, $account->business_id, 'virtual_account.created', [
            'id'=>$account->id, 'reference'=>$account->reference, 'customer_id'=>$account->customer_id,
            'account_number'=>$account->account_number, 'account_name'=>$account->account_name, 'bank_name'=>$account->bank_name,
        ]);
    }

    private function sendBusinessEvent(int $merchantId, int $businessId, string $event, array $payload, ?int $transactionId = null): ?WebhookLog
    {
        $endpoint = WebhookEndpoint::query()->where('merchant_id',$merchantId)->where('business_id',$businessId)
            ->where('environment', WebhookEndpoint::ENVIRONMENT_LIVE)->where('status',WebhookEndpoint::STATUS_ACTIVE)->first();
        if (! $endpoint || ! $endpoint->subscribesTo($event)) return null;
        return $this->queue($endpoint, $event, ['event'=>$event]+$payload, $transactionId);
    }

    public function sendTest(WebhookEndpoint $endpoint): WebhookLog
    {
        return $this->queue($endpoint, 'webhook.test', [
            'event' => 'webhook.test',
            'message' => 'This is a test webhook from MGS API.',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function retry(WebhookLog $log): WebhookLog
    {
        $endpoint = $log->endpoint;

        if (! $endpoint || ! $endpoint->isActive()) {
            $log->update([
                'status' => WebhookLog::STATUS_FAILED,
                'last_error' => 'Webhook endpoint is unavailable or disabled.',
                'next_retry_at' => null,
            ]);

            return $log->fresh();
        }

        if ($log->status === WebhookLog::STATUS_DELIVERED) {
            return $log;
        }

        if ((int) $log->attempt >= self::MAX_ATTEMPTS) {
            $log->update([
                'last_error' => 'Maximum webhook delivery attempts reached.',
                'next_retry_at' => null,
            ]);

            return $log->fresh();
        }

        $log->update([
            'status' => WebhookLog::STATUS_PENDING,
            'next_retry_at' => null,
        ]);

        SendWebhookJob::dispatch($log->id)->afterCommit();

        return $log->fresh();
    }

    public function deliverLog(WebhookLog $log): WebhookLog
    {
        return DB::transaction(function () use ($log): WebhookLog {
            $locked = WebhookLog::query()->lockForUpdate()->findOrFail($log->id);

            if ($locked->status === WebhookLog::STATUS_DELIVERED || (int) $locked->attempt >= self::MAX_ATTEMPTS) {
                return $locked;
            }

            $endpoint = $locked->endpoint;

            if (! $endpoint || ! $endpoint->isActive()) {
                $locked->update([
                    'status' => WebhookLog::STATUS_FAILED,
                    'last_error' => 'Webhook endpoint is unavailable or disabled.',
                    'next_retry_at' => null,
                ]);

                return $locked->fresh();
            }

            $attempt = (int) $locked->attempt + 1;
            $json = in_array($locked->event, ['collection.success', 'payment.success'], true)
                ? \App\Support\Money::jsonDecimals($locked->payload ?? [], ['amount', 'fee', 'merchant_amount'])
                : ($locked->event === 'payment.failed' ? \App\Support\Money::jsonDecimals($locked->payload ?? [], ['amount']) : json_encode($locked->payload ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
            $deliveryId = 'whdel_' . Str::ulid();
            $timestamp = (string) now()->timestamp;
            $signedPayload = $timestamp . '.' . $json;

            $headers = [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'User-Agent' => 'MGS-Webhooks/1.0',
                'X-MGS-Event' => $locked->event,
                'X-MGS-Delivery' => $deliveryId,
                'X-MGS-Timestamp' => $timestamp,
                'X-MGS-Signature' => 'sha256=' . hash_hmac('sha256', $signedPayload, $endpoint->secret),
            ];

            $locked->update([
                'status' => WebhookLog::STATUS_PENDING,
                'request_headers' => $headers,
                'attempt' => $attempt,
                'last_error' => null,
            ]);

            try {
                $options = app(WebhookUrlGuard::class)->options($endpoint->url);
                $response = Http::connectTimeout(5)
                    ->withOptions($options)
                    ->timeout(15)
                    ->withHeaders($headers)
                    ->withBody($json, 'application/json')
                    ->post($endpoint->url);

                $successful = $response->successful();
                $retryable = $response->serverError() || in_array($response->status(), [408, 409, 425, 429], true);

                $locked->update([
                    'status_code' => $response->status(),
                    'response_body' => mb_substr($response->body(), 0, 10000),
                    'status' => $successful ? WebhookLog::STATUS_DELIVERED : WebhookLog::STATUS_FAILED,
                    'last_error' => $successful ? null : "Endpoint returned HTTP {$response->status()}.",
                    'delivered_at' => $successful ? now() : null,
                    'next_retry_at' => (! $successful && $retryable && $attempt < self::MAX_ATTEMPTS)
                        ? now()->addSeconds($this->retryDelaySeconds($attempt))
                        : null,
                ]);
            } catch (Throwable $exception) {
                report($exception);

                $locked->update([
                    'status' => WebhookLog::STATUS_FAILED,
                    'last_error' => mb_substr($exception->getMessage(), 0, 2000),
                    'delivered_at' => null,
                    'next_retry_at' => $attempt < self::MAX_ATTEMPTS
                        ? now()->addSeconds($this->retryDelaySeconds($attempt))
                        : null,
                ]);
            }

            return $locked->fresh();
        }, 5);
    }

    private function queue(
        WebhookEndpoint $endpoint,
        string $event,
        array $payload,
        ?int $transactionId = null
    ): WebhookLog {
        $log = WebhookLog::query()->create([
            'webhook_endpoint_id' => $endpoint->id,
            'merchant_id' => $endpoint->merchant_id,
            'business_id' => $endpoint->business_id,
            'transaction_id' => $transactionId,
            'event' => $event,
            'url' => $endpoint->url,
            'payload' => $payload,
            'attempt' => 0,
            'status' => WebhookLog::STATUS_PENDING,
        ]);

        SendWebhookJob::dispatch($log->id)->afterCommit();

        return $log;
    }

    private function retryDelaySeconds(int $attempt): int
    {
        return min(3600, 30 * (2 ** max(0, $attempt - 1)));
    }
}
