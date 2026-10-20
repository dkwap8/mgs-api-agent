<?php

namespace App\Services;

use App\Jobs\SendNotificationJob;
use App\Models\Notification;
use App\Models\NotificationDelivery;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Throwable;

class NotificationService
{
    public const TYPE_CUSTOMER_REGISTERED = 'customer_registered';
    public const TYPE_KYC_VERIFIED = 'kyc_verified';
    public const TYPE_VIRTUAL_ACCOUNT_CREATED = 'virtual_account_created';
    public const TYPE_VIRTUAL_ACCOUNT_FAILED = 'virtual_account_failed';
    public const TYPE_BUSINESS_APPROVED = 'business_approved';
    public const TYPE_BUSINESS_REJECTED = 'business_rejected';
    public const TYPE_PAYOUT_APPROVED = 'payout_approved';
    public const TYPE_PAYOUT_REJECTED = 'payout_rejected';
    public const TYPE_WALLET_CREDITED = 'wallet_credited';
    public const TYPE_WALLET_DEBITED = 'wallet_debited';
    public const TYPE_PROVIDER_INCIDENT = 'provider_incident';
    public const TYPE_SYSTEM_ALERT = 'system_alert';

    public function notifyAdmin(string $type, string $title, string $message, array $data = []): ?Notification
    {
        return $this->store(null, $type, $title, $message, $data);
    }

    public function notifyUser(int $userId, string $type, string $title, string $message, array $data = [], bool $email = true): ?Notification
    {
        try {
            return DB::transaction(function () use ($userId, $type, $title, $message, $data, $email): Notification {
                $notification = $this->store($userId, $type, $title, $message, $data);

                if ($email) {
                    $user = User::query()->find($userId);
                    if ($user?->email) {
                        $delivery = NotificationDelivery::query()->create([
                            'notification_id' => $notification->id,
                            'user_id' => $userId,
                            'channel' => 'mail',
                            'destination' => $user->email,
                            'subject' => $title,
                            'payload' => ['body' => $message],
                            'status' => NotificationDelivery::STATUS_PENDING,
                        ]);
                        SendNotificationJob::dispatch($delivery->id)->afterCommit();
                    }
                }

                return $notification;
            }, 3);
        } catch (Throwable $exception) {
            report($exception);
            return null;
        }
    }

    public function walletCredited(int $userId, int $walletTransactionId, string $reference, string $amount, string $currency): ?Notification
    {
        return $this->notifyUser(
            $userId,
            self::TYPE_WALLET_CREDITED,
            'Wallet credited',
            "Your wallet was credited with {$currency} {$amount}. Reference: {$reference}.",
            ['wallet_transaction_id' => $walletTransactionId, 'reference' => $reference, 'amount' => $amount, 'currency' => $currency]
        );
    }

    public function walletDebited(int $userId, int $walletTransactionId, string $reference, string $amount, string $currency): ?Notification
    {
        return $this->notifyUser(
            $userId,
            self::TYPE_WALLET_DEBITED,
            'Wallet debited',
            "Your wallet was debited by {$currency} {$amount}. Reference: {$reference}.",
            ['wallet_transaction_id' => $walletTransactionId, 'reference' => $reference, 'amount' => $amount, 'currency' => $currency]
        );
    }

    public function customerRegistered(int $customerId, int $merchantId, int $businessId, string $customerName, string $businessName): ?Notification
    {
        return $this->notifyAdmin(self::TYPE_CUSTOMER_REGISTERED, 'New customer registered', "{$customerName} registered under {$businessName}.", compact('customerId', 'merchantId', 'businessId', 'customerName', 'businessName'));
    }

    public function kycVerified(int $customerId, int $merchantId, int $businessId, string $customerName, string $verificationType): ?Notification
    {
        return $this->notifyAdmin(self::TYPE_KYC_VERIFIED, 'Customer KYC verified', "{$customerName} completed ".strtoupper($verificationType).' verification.', [
            'customer_id' => $customerId, 'merchant_id' => $merchantId, 'business_id' => $businessId, 'verification_type' => $verificationType,
        ]);
    }

    public function virtualAccountCreated(int $customerId, int $merchantId, int $businessId, int $virtualAccountId, string $accountNumber, string $bankName): ?Notification
    {
        return $this->notifyAdmin(self::TYPE_VIRTUAL_ACCOUNT_CREATED, 'Virtual account created', "A {$bankName} virtual account was created successfully.", [
            'customer_id' => $customerId, 'merchant_id' => $merchantId, 'business_id' => $businessId,
            'virtual_account_id' => $virtualAccountId, 'account_number' => $accountNumber, 'bank_name' => $bankName,
        ]);
    }

    public function virtualAccountFailed(int $customerId, int $merchantId, int $businessId, string $reason): ?Notification
    {
        return $this->notifyAdmin(self::TYPE_VIRTUAL_ACCOUNT_FAILED, 'Virtual account generation failed', $reason, compact('customerId', 'merchantId', 'businessId', 'reason'));
    }

    public function businessApproved(int $activationId, int $merchantId, int $businessId, string $businessName): ?Notification
    {
        return $this->notifyAdmin(self::TYPE_BUSINESS_APPROVED, 'Business approved', "{$businessName} was approved successfully.", [
            'activation_id' => $activationId, 'merchant_id' => $merchantId, 'business_id' => $businessId, 'business_name' => $businessName,
        ]);
    }

    public function businessRejected(int $activationId, int $merchantId, ?int $businessId, string $businessName, string $reason): ?Notification
    {
        return $this->notifyAdmin(self::TYPE_BUSINESS_REJECTED, 'Business rejected', "{$businessName} was rejected.", [
            'activation_id' => $activationId, 'merchant_id' => $merchantId, 'business_id' => $businessId, 'business_name' => $businessName, 'reason' => $reason,
        ]);
    }

    public function payoutApproved(int $payoutId, int $merchantId, ?int $businessId, string $reference, float $amount): ?Notification
    {
        return $this->notifyAdmin(self::TYPE_PAYOUT_APPROVED, 'Payout approved', "Payout {$reference} was approved successfully.", compact('payoutId', 'merchantId', 'businessId', 'reference', 'amount'));
    }

    public function payoutRejected(int $payoutId, int $merchantId, ?int $businessId, string $reference, float $amount, string $reason): ?Notification
    {
        return $this->notifyAdmin(self::TYPE_PAYOUT_REJECTED, 'Payout rejected', "Payout {$reference} was rejected.", compact('payoutId', 'merchantId', 'businessId', 'reference', 'amount', 'reason'));
    }

    private function store(?int $userId, string $type, string $title, string $message, array $data): Notification
    {
        return Notification::query()->create([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'is_read' => false,
            'data' => $data,
        ]);
    }
}
