<?php

namespace Tests\Unit;

use App\Http\Middleware\NormalizeApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VerificationNotificationContractTest extends TestCase
{
    #[Test]
    public function it_adds_a_success_notification_to_mutating_api_responses(): void
    {
        $request = Request::create('/api/v1/virtual-accounts', 'POST');
        $request->attributes->set('request_id', 'request-123');

        $response = (new NormalizeApiResponse())->handle(
            $request,
            fn () => new JsonResponse(['message' => 'Virtual account created.'], 201)
        );

        $payload = $response->getData(true);

        $this->assertTrue($payload['success']);
        $this->assertSame('request-123', $payload['request_id']);
        $this->assertSame('success', $payload['notification']['type']);
        $this->assertTrue($payload['notification']['auto_show']);
    }

    #[Test]
    public function it_marks_validation_failures_as_warning_notifications(): void
    {
        $request = Request::create('/api/v1/kyc/bvn', 'POST');

        $response = (new NormalizeApiResponse())->handle(
            $request,
            fn () => new JsonResponse([
                'success' => false,
                'message' => 'The submitted data is invalid.',
                'code' => 'VALIDATION_FAILED',
                'errors' => ['bvn' => ['The BVN field is required.']],
            ], 422)
        );

        $payload = $response->getData(true);

        $this->assertFalse($payload['success']);
        $this->assertSame('warning', $payload['notification']['type']);
        $this->assertStringContainsString('Identity verification', $payload['notification']['title']);
    }

    #[Test]
    public function successful_read_requests_are_silent_by_default(): void
    {
        $request = Request::create('/api/v1/providers', 'GET');

        $response = (new NormalizeApiResponse())->handle(
            $request,
            fn () => new JsonResponse(['success' => true, 'data' => []])
        );

        $this->assertFalse($response->getData(true)['notification']['auto_show']);
    }
}
