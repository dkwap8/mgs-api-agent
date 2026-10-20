<?php

namespace App\Http\Controllers\Api\Merchant;

use App\Http\Controllers\Controller;
use App\Models\WebhookEndpoint;
use App\Models\WebhookLog;
use App\Services\MerchantWebhookService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MerchantWebhookController extends Controller
{
    use ApiResponseTrait;

    public const SUPPORTED_EVENTS = [
        'collection.success', 'payment.success', 'payment.failed', 'payout.success', 'payout.failed', 'virtual_account.created',
    ];

    public function __construct(
        protected MerchantWebhookService $merchantWebhookService
    ) {
    }

    public function show(Request $request): JsonResponse
    {
        $merchant = $request->activeMerchant();
        $business = $request->activeBusiness();

        if (! $merchant || ! $business) {
            return $this->errorResponse(
                'Please select an active business.',
                422
            );
        }

        $endpoint = WebhookEndpoint::query()
            ->where('merchant_id', $merchant->id)
            ->where('business_id', $business->id)
            ->first();

        return $this->successResponse([
            'endpoint' => $endpoint,
            'supported_events' => self::SUPPORTED_EVENTS,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $merchant = $request->activeMerchant();
        $business = $request->activeBusiness();

        if (! $merchant || ! $business) {
            return $this->errorResponse(
                'Please select an active business.',
                422
            );
        }

        $validated = $request->validate([
            'url' => [
                'required',
                'url',
                'max:1000',
                function (
                    string $attribute,
                    mixed $value,
                    \Closure $fail
                ): void {
                    try {
                        app(\App\Services\WebhookUrlGuard::class)->options((string) $value);
                    } catch (\RuntimeException $exception) {
                        $fail($exception->getMessage());
                    }
                },
            ],

            'events' => [
                'required',
                'array',
                'min:1',
            ],

            'events.*' => [
                'string',
                Rule::in(self::SUPPORTED_EVENTS),
            ],

            'status' => [
                'sometimes',
                Rule::in([
                    WebhookEndpoint::STATUS_ACTIVE,
                    WebhookEndpoint::STATUS_DISABLED,
                ]),
            ],

            'regenerate_secret' => [
                'sometimes',
                'boolean',
            ],
        ]);

        $endpoint = WebhookEndpoint::query()
            ->where('merchant_id', $merchant->id)
            ->where('business_id', $business->id)
            ->first();

        $secret = $endpoint?->secret;

        if (! $secret || ($validated['regenerate_secret'] ?? false)) {
            $secret = 'whsec_' . Str::random(48);
        }

        $endpoint = WebhookEndpoint::query()->updateOrCreate(
            [
                'merchant_id' => $merchant->id,
                'business_id' => $business->id,
            ],
            [
                'url' => trim($validated['url']),
                'secret' => $secret,
                'environment' =>
                    $merchant->live_access
                        ? WebhookEndpoint::ENVIRONMENT_LIVE
                        : WebhookEndpoint::ENVIRONMENT_TEST,
                'status' =>
                    $validated['status']
                    ?? WebhookEndpoint::STATUS_ACTIVE,
                'events' => array_values(
                    array_unique($validated['events'])
                ),
                'metadata' => array_merge(
                    $endpoint?->metadata ?? [],
                    [
                        'updated_by_user_id' =>
                            $request->user()?->id,
                    ]
                ),
            ]
        );

        return $this->successResponse([
            'endpoint' => $endpoint,
            'secret' => $secret,
            'supported_events' => self::SUPPORTED_EVENTS,
        ], 'Webhook configuration saved successfully.');
    }

    public function logs(Request $request): JsonResponse
    {
        $merchant = $request->activeMerchant();
        $business = $request->activeBusiness();

        if (! $merchant || ! $business) {
            return $this->errorResponse(
                'Please select an active business.',
                422
            );
        }

        $validated = $request->validate([
            'status' => [
                'nullable',
                Rule::in([
                    WebhookLog::STATUS_PENDING,
                    WebhookLog::STATUS_DELIVERED,
                    WebhookLog::STATUS_FAILED,
                ]),
            ],
            'per_page' => [
                'nullable',
                'integer',
                'min:5',
                'max:100',
            ],
        ]);

        $query = WebhookLog::query()
            ->where('merchant_id', $merchant->id)
            ->where('business_id', $business->id)
            ->latest('id');

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        return $this->successResponse(
            $query->paginate(
                (int) ($validated['per_page'] ?? 20)
            )
        );
    }

    public function test(Request $request): JsonResponse
    {
        $merchant = $request->activeMerchant();
        $business = $request->activeBusiness();

        if (! $merchant || ! $business) {
            return $this->errorResponse(
                'Please select an active business.',
                422
            );
        }

        $endpoint = WebhookEndpoint::query()
            ->where('merchant_id', $merchant->id)
            ->where('business_id', $business->id)
            ->first();

        if (! $endpoint) {
            return $this->errorResponse(
                'Please save a webhook endpoint first.',
                422
            );
        }

        $log = $this->merchantWebhookService->sendTest(
            $endpoint
        );

        return $this->successResponse(
            $log,
            'Webhook test queued for delivery.'
        );
    }

    public function retry(
        int $id,
        Request $request
    ): JsonResponse {
        $merchant = $request->activeMerchant();
        $business = $request->activeBusiness();

        if (! $merchant || ! $business) {
            return $this->errorResponse(
                'Please select an active business.',
                422
            );
        }

        $log = WebhookLog::query()
            ->where('merchant_id', $merchant->id)
            ->where('business_id', $business->id)
            ->findOrFail($id);

        $retried = $this->merchantWebhookService
            ->retry($log);

        return $this->successResponse(
            $retried,
            'Webhook retry queued.'
        );
    }
}