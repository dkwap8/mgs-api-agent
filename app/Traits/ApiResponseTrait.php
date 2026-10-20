<?php

namespace App\Traits;

use App\Support\ApiResponse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;

trait ApiResponseTrait
{
    protected function successResponse(mixed $data = null, string $message = 'Success', int $code = Response::HTTP_OK, array $meta = []): JsonResponse
    {
        return ApiResponse::success($data, $message, $code, $meta);
    }

    protected function paginatedResponse(LengthAwarePaginator $paginator, string $resourceClass, ?string $message = null): JsonResponse
    {
        /** @var class-string<JsonResource> $resourceClass */
        return ApiResponse::paginated($paginator, $resourceClass, $message);
    }

    protected function errorResponse(string $message, int $code = Response::HTTP_BAD_REQUEST, ?array $errors = null, ?string $errorCode = null): JsonResponse
    {
        return ApiResponse::error($message, $code, $errors ?? [], $errorCode);
    }

    protected function notFoundResponse(string $message = 'Resource not found'): JsonResponse
    {
        return $this->errorResponse($message, Response::HTTP_NOT_FOUND, errorCode: 'RESOURCE_NOT_FOUND');
    }

    protected function unauthorizedResponse(string $message = 'Unauthorised'): JsonResponse
    {
        return $this->errorResponse($message, Response::HTTP_UNAUTHORIZED, errorCode: 'UNAUTHENTICATED');
    }

    protected function forbiddenResponse(string $message = 'Forbidden'): JsonResponse
    {
        return $this->errorResponse($message, Response::HTTP_FORBIDDEN, errorCode: 'FORBIDDEN');
    }

    protected function validationErrorResponse(array $errors): JsonResponse
    {
        return $this->errorResponse('Validation failed.', Response::HTTP_UNPROCESSABLE_ENTITY, $errors, 'VALIDATION_ERROR');
    }
}
