<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessProviderWebhook;
use App\Jobs\ProcessCollectionWebhookJob;
use App\Models\Provider;
use App\Services\CollectionService;
use App\Services\WebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class WebhookController extends Controller
{
    public function __construct(
        protected WebhookService $webhookService,
        protected CollectionService $collectionService
    ) {
    }

    public function provider(
        Request $request,
        Provider $provider
    ): JsonResponse {
        try {
            if (! $provider->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Provider is inactive.',
                ], 422);
            }

            $this->webhookService->verifySignature(
                $provider,
                $request
            );

            $normalized = $this->webhookService
                ->normalizeProviderPayload(
                    $provider,
                    $request->all()
                );

            if ($normalized['type'] === 'payout') {
                ProcessProviderWebhook::dispatch(
                    $provider->id,
                    $normalized
                )->afterCommit();

                return response()->json([
                    'success' => true,
                    'message' => 'Payout webhook accepted for processing.',
                    'data' => [
                        'reference' => $normalized['reference'],
                        'status' => $normalized['status'],
                    ],
                ], 202);
            }

            ProcessCollectionWebhookJob::dispatch(
                $provider->id,
                $normalized
            )->afterCommit();

            return response()->json([
                'success' => true,
                'message' => 'Collection webhook accepted for verification.',
                'data' => [
                    'provider_reference' => $normalized['provider_reference'],
                    'status' => 'queued',
                ],
            ], 202);
        } catch (RuntimeException $exception) {
            Log::warning('Provider webhook rejected', [
                'provider_id' => $provider->id,
                'provider' => $provider->name,
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Webhook processing failed.',
            ], 500);
        }
    }
}