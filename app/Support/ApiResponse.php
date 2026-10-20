<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

final class ApiResponse
{
    public static function success(mixed $data = null, ?string $message = null, int $status = 200, array $meta = []): JsonResponse
    {
        $payload = [
            'success' => true,
            'request_id' => request()?->attributes->get('request_id'),
        ];

        if ($message !== null) {
            $payload['message'] = $message;
        }

        if ($data !== null) {
            $payload['data'] = $data;
        }

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }

    public static function error(string $message, int $status = 400, array $errors = [], ?string $code = null): JsonResponse
    {
        $payload = [
            'success' => false,
            'message' => $message,
            'request_id' => request()?->attributes->get('request_id'),
        ];

        if ($code !== null) {
            $payload['code'] = $code;
        }

        if ($errors !== []) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }

    public static function paginated(LengthAwarePaginator $paginator, string $resourceClass, ?string $message = null): JsonResponse
    {
        /** @var class-string<JsonResource> $resourceClass */
        return self::success(
            $resourceClass::collection($paginator->getCollection()),
            $message,
            200,
            [
                'current_page' => $paginator->currentPage(),
                'from' => $paginator->firstItem(),
                'last_page' => $paginator->lastPage(),
                'path' => $paginator->path(),
                'per_page' => $paginator->perPage(),
                'to' => $paginator->lastItem(),
                'total' => $paginator->total(),
            ]
        )->withHeaders([
            'Link' => self::paginationLinkHeader($paginator),
        ]);
    }

    private static function paginationLinkHeader(LengthAwarePaginator $paginator): string
    {
        return collect([
            'first' => $paginator->url(1),
            'last' => $paginator->url($paginator->lastPage()),
            'prev' => $paginator->previousPageUrl(),
            'next' => $paginator->nextPageUrl(),
        ])->filter()->map(fn (string $url, string $rel): string => sprintf('<%s>; rel="%s"', $url, $rel))->implode(', ');
    }
}
