<?php

namespace App\Services;

use App\Models\Provider;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use RuntimeException;

class WebhookService
{
    public function verifySignature(Provider $provider, Request $request): void
    {
        $config = $provider->config ?? [];
        $metadata = $provider->metadata ?? [];
        $secret = trim((string) ($config['webhook_secret'] ?? $config['secret_key'] ?? ''));

        if ($secret === '') {
            throw new RuntimeException('Webhook secret is not configured for this provider.');
        }

        if (strtolower((string) $provider->slug) === 'monnify') {
            $received = trim((string) $request->header('monnify-signature', ''));
            $expected = hash_hmac('sha512', $request->getContent(), (string) ($config['secret_key'] ?? ''));
            if (empty($config['secret_key']) || ! hash_equals($expected, strtolower($received))) {
                throw new RuntimeException('Webhook signature verification failed.');
            }
            return;
        }

        $headers = (array) Arr::get($metadata, 'webhook.signature_headers', [
            'X-Webhook-Signature', 'X-Signature', 'Signature',
        ]);
        $received = '';

        foreach ($headers as $header) {
            $value = trim((string) $request->header((string) $header, ''));

            if ($value !== '') {
                $received = $value;
                break;
            }
        }

        if ($received === '') {
            throw new RuntimeException('Webhook signature is missing.');
        }

        $algorithm = strtolower((string) Arr::get($metadata, 'webhook.algorithm', 'sha256'));

        if (! in_array($algorithm, hash_hmac_algos(), true)) {
            throw new RuntimeException('The configured webhook signature algorithm is not supported.');
        }

        $prefix = (string) Arr::get($metadata, 'webhook.signature_prefix', $algorithm . '=');

        if ($prefix !== '' && str_starts_with(strtolower($received), strtolower($prefix))) {
            $received = substr($received, strlen($prefix));
        }

        $signedContent = match ((string) Arr::get($metadata, 'webhook.signed_content', 'raw_body')) {
            'timestamp_body' => trim((string) $request->header(
                (string) Arr::get($metadata, 'webhook.timestamp_header', 'X-Webhook-Timestamp'),
                ''
            )) . '.' . $request->getContent(),
            default => $request->getContent(),
        };

        $encoding = strtolower((string) Arr::get($metadata, 'webhook.encoding', 'hex'));
        $binary = hash_hmac($algorithm, $signedContent, $secret, true);
        $expected = $encoding === 'base64' ? base64_encode($binary) : bin2hex($binary);

        if (! hash_equals($encoding === 'base64' ? $expected : strtolower($expected), $encoding === 'base64' ? $received : strtolower($received))) {
            throw new RuntimeException('Webhook signature verification failed.');
        }


        $timestampHeader = (string) Arr::get($metadata, 'webhook.timestamp_header', 'X-Webhook-Timestamp');
        $timestamp = trim((string) $request->header($timestampHeader, ''));
        $tolerance = max(0, (int) Arr::get($metadata, 'webhook.timestamp_tolerance_seconds', 300));

        if ($timestamp !== '' && $tolerance > 0) {
            $receivedAt = is_numeric($timestamp)
                ? Carbon::createFromTimestamp((int) $timestamp)
                : Carbon::parse($timestamp);

            if (abs(now()->diffInSeconds($receivedAt, false)) > $tolerance) {
                throw new RuntimeException('Webhook timestamp is outside the allowed tolerance.');
            }
        }
    }


    public function normalizeProviderPayload(Provider $provider, array $payload): array
    {
        if (strtolower((string) $provider->slug) === 'monnify') {
            $event = strtoupper((string) ($payload['eventType'] ?? ''));
            if (in_array($event, ['SUCCESSFUL_DISBURSEMENT', 'FAILED_DISBURSEMENT', 'REVERSED_DISBURSEMENT'], true)) {
                $reference = Arr::get($payload, 'eventData.reference');
                if (! is_string($reference) || $reference === '') {
                    throw new RuntimeException('Payout webhook reference is missing.');
                }
                return [
                    'type' => 'payout', 'event' => strtolower($event),
                    'reference' => $reference,
                    'provider_reference' => (string) Arr::get($payload, 'eventData.transactionReference', $reference),
                    'status' => strtolower((string) Arr::get($payload, 'eventData.status', 'unknown')),
                    'message' => (string) Arr::get($payload, 'eventData.transactionDescription', ''),
                    'raw_payload' => $payload,
                ];
            }
            if ($event !== 'SUCCESSFUL_TRANSACTION') {
                throw new RuntimeException('Unsupported Monnify event type.');
            }
            if (Arr::get($payload, 'eventData.currency') !== 'NGN') {
                throw new RuntimeException('Unsupported collection currency.');
            }
        }
        $event = strtolower((string) $this->firstNonEmpty($payload, [
            'event', 'event_type', 'type', 'data.event', 'data.event_type',
        ], ''));

        $reference = $this->firstNonEmpty($payload, [
            'provider_reference', 'transaction_reference', 'transactionReference',
            'payment_reference', 'paymentReference', 'reference',
            'data.provider_reference', 'data.reference',
            'data.transaction_reference', 'data.transactionReference',
        ]);

        $status = strtolower((string) $this->firstNonEmpty($payload, [
            'status', 'payment_status', 'paymentStatus', 'transaction_status',
            'transactionStatus', 'data.status', 'data.payment_status',
            'data.transaction_status',
        ], 'unknown'));

        $isPayout = str_contains($event, 'payout')
            || str_contains($event, 'transfer')
            || str_contains($event, 'withdrawal');

        if ($isPayout) {
            if (! $reference) {
                throw new RuntimeException('Payout webhook reference is missing.');
            }

            return [
                'type' => 'payout',
                'event' => $event ?: 'payout.status',
                'reference' => (string) $reference,
                'status' => $status,
                'message' => (string) $this->firstNonEmpty($payload, [
                    'message', 'description', 'data.message', 'data.description',
                ], ''),
                'provider_reference' => (string) $this->firstNonEmpty($payload, [
                    'provider_reference', 'data.provider_reference', 'reference', 'data.reference',
                ], $reference),
                'raw_payload' => $payload,
            ];
        }

        return array_merge(['type' => 'collection'], $this->normalizeCollectionPayload($provider, $payload));
    }

    public function normalizeCollectionPayload(
        Provider $provider,
        array $payload
    ): array {
        $providerReference = $this->firstNonEmpty($payload, [
            'provider_reference',
            'transaction_reference',
            'transactionReference',
            'payment_reference',
            'paymentReference',
            'reference',
            'session_id',
            'sessionId',
            'data.reference',
            'data.transaction_reference',
            'data.transactionReference',
            'data.payment_reference',
            'data.paymentReference',
            'data.session_id',
            'eventData.reference',
            'eventData.transactionReference',
        ]);

        $accountNumber = $this->firstNonEmpty($payload, [
            'account_number',
            'accountNumber',
            'destination_account_number',
            'destinationAccountNumber',
            'virtual_account_number',
            'virtualAccountNumber',
            'data.account_number',
            'data.accountNumber',
            'data.destination_account_number',
            'data.destinationAccountNumber',
            'data.virtual_account_number',
            'eventData.accountNumber',
            'eventData.destinationAccountNumber',
            'eventData.destinationAccountInformation.accountNumber',
        ]);

        $amount = $this->firstNonEmpty($payload, [
            'amount',
            'paid_amount',
            'paidAmount',
            'transaction_amount',
            'transactionAmount',
            'data.amount',
            'data.paid_amount',
            'data.paidAmount',
            'data.transaction_amount',
            'data.transactionAmount',
            'eventData.amountPaid',
            'eventData.amount',
        ]);

        $status = strtolower((string) $this->firstNonEmpty($payload, [
            'status',
            'payment_status',
            'paymentStatus',
            'transaction_status',
            'transactionStatus',
            'data.status',
            'data.payment_status',
            'data.paymentStatus',
            'data.transaction_status',
            'eventData.paymentStatus',
            'eventData.status',
        ], 'success'));

        if (! $providerReference) {
            throw new RuntimeException(
                'Provider transaction reference is missing.'
            );
        }

        if (! $accountNumber) {
            throw new RuntimeException(
                'Virtual account number is missing.'
            );
        }

        $amount = \App\Support\Money::positive($amount);

        if ($amount <= 0) {
            throw new RuntimeException(
                'Webhook amount must be greater than zero.'
            );
        }

        return [
            'provider_id' => $provider->id,
            'provider_reference' => (string) $providerReference,
            'account_number' => preg_replace(
                '/\s+/',
                '',
                (string) $accountNumber
            ),
            'amount' => $amount,
            'status' => $status,
            'description' => (string) $this->firstNonEmpty($payload, [
                'description',
                'narration',
                'payment_description',
                'data.description',
                'data.narration',
                'eventData.narration',
            ], 'Virtual account funding'),
            'sender_name' => $this->firstNonEmpty($payload, [
                'sender_name',
                'senderName',
                'customer_name',
                'customerName',
                'data.sender_name',
                'data.senderName',
                'eventData.customerName',
            ]),
            'sender_account_number' => $this->firstNonEmpty($payload, [
                'sender_account_number',
                'senderAccountNumber',
                'source_account_number',
                'sourceAccountNumber',
                'data.sender_account_number',
                'eventData.sourceAccountNumber',
            ]),
            'sender_bank_name' => $this->firstNonEmpty($payload, [
                'sender_bank_name',
                'senderBankName',
                'source_bank_name',
                'sourceBankName',
                'data.sender_bank_name',
                'eventData.sourceBankName',
            ]),
            'raw_payload' => $payload,
        ];
    }

    private function firstNonEmpty(
        array $payload,
        array $keys,
        mixed $default = null
    ): mixed {
        foreach ($keys as $key) {
            $value = Arr::get($payload, $key);

            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return $default;
    }
}
