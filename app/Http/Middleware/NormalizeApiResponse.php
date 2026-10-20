<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeApiResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->is('api/*') || ! $response instanceof JsonResponse) {
            return $response;
        }

        $payload = $response->getData(true);

        if (! is_array($payload)) {
            return $response;
        }

        $status = $response->getStatusCode();
        $legacyStatus = $payload['status'] ?? null;
        $success = $payload['success']
            ?? ($legacyStatus === 'success' ? true : null)
            ?? ($legacyStatus === 'error' ? false : null)
            ?? (is_bool($legacyStatus) ? $legacyStatus : null)
            ?? ($status < 400);

        $requestId = $payload['request_id']
            ?? $request->attributes->get('request_id')
            ?? $response->headers->get('X-Request-ID');

        $normalised = $payload;
        $normalised['success'] = (bool) $success;
        $normalised['request_id'] = $requestId;

        if (array_key_exists('status', $normalised) && (is_bool($normalised['status']) || in_array($normalised['status'], ['success', 'error'], true))) {
            unset($normalised['status']);
        }

        $normalised['notification'] = array_replace_recursive(
            $this->defaultNotification($request, $normalised, $status),
            is_array($payload['notification'] ?? null) ? $payload['notification'] : []
        );

        $response->setData($normalised);

        return $response;
    }

    private function defaultNotification(Request $request, array $payload, int $status): array
    {
        $success = (bool) ($payload['success'] ?? $status < 400);
        $code = (string) ($payload['code'] ?? '');
        $type = $this->notificationType($success, $status, $code);
        $message = (string) ($payload['message'] ?? $this->defaultMessage($request, $success, $status));

        return [
            'type' => $type,
            'title' => $this->titleFor($request, $type, $code),
            'message' => $message,
            'icon' => $type,
            'colour' => match ($type) {
                'success' => 'emerald',
                'warning' => 'amber',
                'error' => 'rose',
                default => 'indigo',
            },
            'request_id' => $payload['request_id'] ?? null,
            'auto_show' => ! $success || ! in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true),
            'dismissible' => true,
            'actions' => $this->actionsFor($status, $code),
        ];
    }

    private function notificationType(bool $success, int $status, string $code): string
    {
        if ($success) {
            return $status === 202 ? 'info' : 'success';
        }

        if ($status === 409 || $status === 422 || str_contains($code, 'PENDING') || str_contains($code, 'REQUIRED')) {
            return 'warning';
        }

        return 'error';
    }

    private function titleFor(Request $request, string $type, string $code): string
    {
        $path = strtolower($request->path());

        $operation = match (true) {
            str_contains($path, 'kyc'), str_contains($path, 'nin'), str_contains($path, 'bvn') => 'Identity verification',
            str_contains($path, 'virtual-account') => 'Virtual account',
            str_contains($path, 'payout'), str_contains($path, 'withdraw') => 'Payout',
            str_contains($path, 'provider') => 'Provider operation',
            str_contains($path, 'webhook') => 'Webhook operation',
            str_contains($path, 'auth'), str_contains($path, 'login'), str_contains($path, 'password') => 'Account security',
            default => 'Platform operation',
        };

        return match ($type) {
            'success' => "$operation successful",
            'warning' => "$operation needs attention",
            'error' => "$operation unsuccessful",
            default => "$operation update",
        };
    }

    private function actionsFor(int $status, string $code): array
    {
        if ($status === 401 || $code === 'UNAUTHENTICATED') {
            return [
                ['id' => 'sign_in', 'label' => 'Sign in', 'style' => 'primary', 'url' => '/login'],
                ['id' => 'close', 'label' => 'Close', 'style' => 'secondary'],
            ];
        }

        if ($status >= 500) {
            return [
                ['id' => 'retry', 'label' => 'Try again', 'style' => 'primary'],
                ['id' => 'close', 'label' => 'Close', 'style' => 'secondary'],
            ];
        }

        return [
            ['id' => 'close', 'label' => $status < 400 ? 'Done' : 'Close', 'style' => 'primary'],
        ];
    }

    private function defaultMessage(Request $request, bool $success, int $status): string
    {
        if ($success) {
            return $status === 202
                ? 'Your request has been accepted and is being processed.'
                : 'Your request was completed successfully.';
        }

        return $status >= 500
            ? 'The service could not complete your request. Please try again.'
            : 'Please review the information provided and try again.';
    }
}
