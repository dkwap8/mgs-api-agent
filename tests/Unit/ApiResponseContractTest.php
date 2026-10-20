<?php

namespace Tests\Unit;

use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Tests\TestCase;

class ApiResponseContractTest extends TestCase
{
    public function test_success_response_uses_the_canonical_envelope(): void
    {
        $request = Request::create('/api/v1/test');
        $request->attributes->set('request_id', 'test-request-123');
        $this->app->instance('request', $request);

        $response = ApiResponse::success(['value' => 1], 'Completed.');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'success' => true,
            'request_id' => 'test-request-123',
            'message' => 'Completed.',
            'data' => ['value' => 1],
        ], $response->getData(true));
    }

    public function test_error_response_uses_the_canonical_envelope(): void
    {
        $request = Request::create('/api/v1/test');
        $request->attributes->set('request_id', 'test-request-456');
        $this->app->instance('request', $request);

        $response = ApiResponse::error('Invalid input.', 422, ['email' => ['Required.']], 'VALIDATION_FAILED');

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame(false, $response->getData(true)['success']);
        $this->assertSame('VALIDATION_FAILED', $response->getData(true)['code']);
        $this->assertSame('test-request-456', $response->getData(true)['request_id']);
    }
}
