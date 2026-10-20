<?php
namespace Tests\Unit;
use App\Services\WebhookUrlGuard;
use PHPUnit\Framework\TestCase;
class WebhookUrlGuardTest extends TestCase
{
    public function test_private_local_mapped_and_non_https_destinations_are_rejected(): void
    {
        foreach (['https://127.0.0.1/hook', 'https://10.1.2.3/hook', 'https://169.254.169.254/', 'https://[::1]/', 'https://[::ffff:127.0.0.1]/', 'http://8.8.8.8/', 'https://user:pass@8.8.8.8/', 'https://100.64.0.1/', 'https://8.8.8.8:8443/'] as $url) {
            try { (new WebhookUrlGuard)->options($url); $this->fail('Unsafe URL accepted: '.$url); }
            catch (\RuntimeException) { $this->addToAssertionCount(1); }
        }
    }
    public function test_public_dns_is_pinned_and_redirects_are_disabled(): void
    {
        $guard = new class extends WebhookUrlGuard { protected function resolve(string $host): array { return ['8.8.8.8']; } };
        $options = $guard->options('https://merchant.example/hook');
        $this->assertFalse($options['allow_redirects']);
        $this->assertSame(['merchant.example:443:8.8.8.8'], $options['curl'][CURLOPT_RESOLVE]);
    }
    public function test_mixed_public_and_private_dns_is_rejected(): void
    {
        $guard = new class extends WebhookUrlGuard { protected function resolve(string $host): array { return ['8.8.8.8', '192.168.1.1']; } };
        $this->expectException(\RuntimeException::class);
        $guard->options('https://merchant.example/hook');
    }
}
