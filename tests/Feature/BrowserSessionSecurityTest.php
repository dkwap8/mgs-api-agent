<?php
namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Support\Facades\Auth;
use Tests\Support\PaymentTestCase;

class BrowserSessionSecurityTest extends PaymentTestCase
{
    private function cookie($response, string $name): \Symfony\Component\HttpFoundation\Cookie
    {
        foreach ($response->headers->getCookies() as $cookie) if ($cookie->getName() === $name) return $cookie;
        throw new \RuntimeException('Expected authentication cookie missing.');
    }

    public function test_browser_user_login_returns_httponly_cookie_without_json_token_and_enforces_csrf(): void
    {
        $response = $this->withHeader('X-Browser-Auth', '1')->postJson('/api/v1/auth/login', ['email' => $this->user->email, 'password' => 'test-password'])->assertOk()->assertJsonPath('data.browser_session', true)->assertJsonMissingPath('data.token');
        $session = $this->cookie($response, 'mgs_user_session');
        $csrf = $this->cookie($response, 'mgs_user_csrf');
        $this->assertTrue($session->isHttpOnly());
        $this->assertSame('strict', $session->getSameSite());
        $this->assertSame('/api/v1', $session->getPath());
        Auth::forgetGuards();
        $this->withCredentials()->withUnencryptedCookie('mgs_user_session', $session->getValue())->getJson('/api/v1/auth/me')->assertOk();
        Auth::forgetGuards();
        $this->postJson('/api/v1/auth/logout')->assertStatus(419);
        Auth::forgetGuards();
        $this->withHeader('X-MGS-CSRF', $csrf->getValue())->postJson('/api/v1/auth/logout')->assertOk();
    }

    public function test_user_cookie_cannot_authenticate_admin_and_admin_cookie_cannot_authenticate_user(): void
    {
        $userToken = $this->user->createToken('fixture')->plainTextToken;
        $admin = Admin::create(['name' => 'Admin', 'email' => 'cookie-admin@example.test', 'password' => 'test-password', 'role' => 'super_admin', 'status' => 'active']);
        $adminToken = $admin->createToken('fixture', ['admin:access'])->plainTextToken;
        $this->withCredentials()->withUnencryptedCookie('mgs_user_session', $userToken)->getJson('/api/v1/admin/auth/me')->assertUnauthorized();
        Auth::forgetGuards();
        $this->withCredentials()->withUnencryptedCookie('mgs_admin_session', $userToken)->getJson('/api/v1/admin/auth/me')->assertForbidden();
        Auth::forgetGuards();
        $this->withCredentials()->withUnencryptedCookie('mgs_user_session', $adminToken)->getJson('/api/v1/auth/me')->assertForbidden();
    }

    public function test_admin_browser_cookie_is_secure_in_production_and_bearer_api_login_still_works(): void
    {
        config(['admin_security.require_mfa' => false]);
        $admin = Admin::create(['name' => 'Admin', 'email' => 'cookie-admin@example.test', 'password' => 'test-password', 'role' => 'super_admin', 'status' => 'active']);
        app()->detectEnvironment(fn () => 'production');
        $response = $this->withHeader('X-Browser-Auth', '1')->postJson('/api/v1/admin/auth/login', ['email' => $admin->email, 'password' => 'test-password'])->assertOk()->assertJsonMissingPath('data.token');
        $cookie = $this->cookie($response, 'mgs_admin_session');
        $this->assertTrue($cookie->isSecure());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('/api/v1/admin', $cookie->getPath());
        $this->withHeader('X-Browser-Auth', '0')->postJson('/api/v1/auth/login', ['email' => $this->user->email, 'password' => 'test-password'])->assertOk()->assertJsonStructure(['data' => ['token']]);
    }

    public function test_cross_origin_browser_login_is_rejected_before_credentials_are_used(): void
    {
        $this->withHeaders(['X-Browser-Auth' => '1', 'Origin' => 'https://attacker.example'])->postJson('/api/v1/auth/login', ['email' => $this->user->email, 'password' => 'test-password'])->assertForbidden();
        $this->assertSame(0, $this->user->tokens()->count());
    }
}
