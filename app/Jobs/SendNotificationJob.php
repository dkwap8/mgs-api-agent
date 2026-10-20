<?php

namespace App\Jobs;

use App\Models\NotificationDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Throwable;

class SendNotificationJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public int $timeout = 60;
    public array $backoff = [30, 120, 300, 900];

    public function __construct(public readonly int $deliveryId)
    {
        $this->onQueue('notifications');
    }

    public function uniqueId(): string
    {
        return 'notification-delivery-'.$this->deliveryId;
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping($this->uniqueId()))->releaseAfter(30)->expireAfter(120)];
    }

    public function handle(): void
    {
        $delivery = DB::transaction(function (): ?NotificationDelivery {
            $record = NotificationDelivery::query()->lockForUpdate()->find($this->deliveryId);

            if (! $record || in_array($record->status, [NotificationDelivery::STATUS_DELIVERED, NotificationDelivery::STATUS_DEAD], true)) {
                return null;
            }

            if ($record->next_retry_at && $record->next_retry_at->isFuture()) {
                $this->release(max(1, now()->diffInSeconds($record->next_retry_at)));
                return null;
            }

            $record->forceFill([
                'status' => NotificationDelivery::STATUS_PROCESSING,
                'attempts' => $record->attempts + 1,
            ])->save();

            return $record->fresh();
        }, 3);

        if (! $delivery) {
            return;
        }

        try {
            match ($delivery->channel) {
                'mail' => $this->sendMail($delivery),
                default => throw new RuntimeException("Unsupported notification channel [{$delivery->channel}]."),
            };

            $delivery->forceFill([
                'status' => NotificationDelivery::STATUS_DELIVERED,
                'delivered_at' => now(),
                'failed_at' => null,
                'next_retry_at' => null,
                'last_error' => null,
            ])->save();
        } catch (Throwable $exception) {
            $attempts = (int) $delivery->attempts;
            $dead = $attempts >= 5;
            $delay = min(3600, 30 * (2 ** max(0, $attempts - 1)));

            $delivery->forceFill([
                'status' => $dead ? NotificationDelivery::STATUS_DEAD : NotificationDelivery::STATUS_FAILED,
                'failed_at' => now(),
                'next_retry_at' => $dead ? null : now()->addSeconds($delay),
                'last_error' => mb_substr($exception->getMessage(), 0, 2000),
            ])->save();

            if (! $dead) {
                throw $exception;
            }

            report($exception);
        }
    }

    private function sendMail(NotificationDelivery $delivery): void
    {
        $body = (string) data_get($delivery->payload, 'body', '');
        if ($delivery->destination === '' || $body === '') {
            throw new RuntimeException('Notification email destination and body are required.');
        }

        Mail::raw($body, function ($message) use ($delivery): void {
            $message->to($delivery->destination)->subject($delivery->subject ?: config('app.name'));
        });
    }
}
