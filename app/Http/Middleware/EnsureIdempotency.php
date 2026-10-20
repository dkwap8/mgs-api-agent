<?php

namespace App\Http\Middleware;

use App\Models\IdempotencyKey;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class EnsureIdempotency
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = trim((string) $request->header('X-Idempotency-Key'));
        if ($key === '' || strlen($key) < 16 || strlen($key) > 255) {
            return $this->error('A valid X-Idempotency-Key header (16-255 characters) is required.', 422);
        }

        $scope = $this->scope($request);
        $keyHash = hash('sha256', $key);
        $requestHash = hash('sha256', implode('|', [
            strtoupper($request->method()),
            '/'.ltrim($request->path(), '/'),
            hash('sha256', $request->getContent()),
        ]));

        $record = IdempotencyKey::query()->firstOrCreate(
            ['scope' => $scope, 'key_hash' => $keyHash],
            [
                'request_hash' => $requestHash,
                'method' => strtoupper($request->method()),
                'path' => '/'.ltrim($request->path(), '/'),
                'status' => 'processing', 'locked_at' => now(), 'expires_at' => now()->addDay(),
            ]
        );

        if (! hash_equals($record->request_hash, $requestHash)) {
            return $this->error('This idempotency key was already used for a different request.', 409);
        }

        if ($record->status === 'completed') {
            return response()->json(
                $record->response_body ?? [],
                $record->response_status ?? 200,
                array_merge($record->response_headers ?? [], ['X-Idempotent-Replay' => 'true'])
            );
        }

        if (! $record->wasRecentlyCreated) {
            return $this->error('This request is processing or has an unresolved outcome. Reconcile it before submitting another request.', 409);
        }

        try {
            $response = $next($request);
            if ($response instanceof JsonResponse && $response->getStatusCode() < 500) {
                $decoded = json_decode((string) $response->getContent(), true);
                $record->forceFill([
                    'status' => 'completed',
                    'response_status' => $response->getStatusCode(),
                    'response_body' => is_array($decoded) ? $decoded : ['data' => $decoded],
                    'response_headers' => ['Content-Type' => 'application/json'],
                    'completed_at' => now(),
                ])->save();
            } else {
                $record->forceFill(['status' => 'uncertain'])->save();
            }
            return $response;
        } catch (\Throwable $exception) {
            $record->forceFill(['status' => 'uncertain'])->save();
            throw $exception;
        }
    }

    private function scope(Request $request): string
    {
        $apiKey = $request->attributes->get('api_key');
        if ($apiKey) {
            return 'api-key:'.$apiKey->id;
        }

        $business = $request->activeBusiness();
        return 'user:'.($request->user()?->id ?? 'guest').':business:'.($business?->id ?? 'none');
    }

    private function error(string $message, int $status): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message], $status);
    }
}
