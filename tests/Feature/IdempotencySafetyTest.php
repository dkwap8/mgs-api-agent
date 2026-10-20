<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureIdempotency;
use App\Models\IdempotencyKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class IdempotencySafetyTest extends TestCase
{
    use RefreshDatabase;
    private function request(): Request
    {
        $request = Request::create('/api/test', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], '{"amount":100}');
        $request->headers->set('X-Idempotency-Key', 'unique-test-request-123');
        return $request;
    }
    public function test_completed_request_replays_without_executing_twice(): void
    {
        $count = 0;
        $handler = function () use (&$count) { $count++; return response()->json(['payout_id' => 10], 201); };
        $middleware = app(EnsureIdempotency::class);
        $middleware->handle($this->request(), $handler);
        $response = $middleware->handle($this->request(), $handler);
        $this->assertSame(1, $count);
        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('true', $response->headers->get('X-Idempotent-Replay'));
    }
    public function test_ambiguous_error_is_not_reexecuted_even_after_lease_and_prune(): void
    {
        $middleware = app(EnsureIdempotency::class);
        try { $middleware->handle($this->request(), fn () => throw new \RuntimeException('failure after side effect')); }
        catch (\RuntimeException) {}
        $this->travel(3)->days();
        $this->artisan('idempotency:prune')->assertSuccessful();
        $this->assertSame(1, IdempotencyKey::count());
        $response = $middleware->handle($this->request(), function () { $this->fail('Must not run a second mutation'); });
        $this->assertSame(409, $response->getStatusCode());
    }
    public function test_stale_processing_record_is_not_reclaimed(): void
    {
        $middleware = app(EnsureIdempotency::class);
        $middleware->handle($this->request(), fn () => response()->json([], 500));
        IdempotencyKey::query()->update(['status' => 'processing', 'locked_at' => now()->subHour()]);
        $this->assertSame(409, $middleware->handle($this->request(), function () { $this->fail('Must not reclaim'); })->getStatusCode());
    }
}
