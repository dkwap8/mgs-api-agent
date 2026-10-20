<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\BusinessActivation;
use App\Services\AdminMfaService;
use App\Services\TotpService;
use Illuminate\Support\Facades\Storage;
use Tests\Support\PaymentTestCase;

class AuthenticationAndDocumentsTest extends PaymentTestCase
{
    private function admin(): Admin
    {
        return Admin::create(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'test-password', 'role' => 'super_admin', 'status' => 'active']);
    }

    public function test_user_token_cannot_access_admin_and_admin_token_cannot_access_user_endpoints(): void
    {
        $userToken = $this->user->createToken('user')->plainTextToken;
        $this->withToken($userToken)->getJson('/api/v1/admin/business-activations')->assertForbidden();
        auth()->forgetGuards();
        $adminToken = $this->admin()->createToken('admin', ['admin:access'])->plainTextToken;
        foreach (['/api/v1/auth/me', '/api/v1/merchant/businesses', '/api/v1/merchant/profile', '/api/v1/support/tickets'] as $url) {
            auth()->forgetGuards();
            $this->withToken($adminToken)->getJson($url)->assertForbidden();
        }
    }

    public function test_document_requires_authentication_permission_and_ownership(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $activation = BusinessActivation::first();
        $path = 'activations/proof-address/test.pdf';
        Storage::disk('local')->put($path, "%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF");
        $activation->update(['proof_of_address' => $path]);
        $adminUrl = '/api/v1/admin/business-activations/'.$activation->id.'/documents/proof_of_address';
        $merchantUrl = '/api/v1/merchant/activations/'.$activation->id.'/documents/proof_of_address';
        $this->getJson($adminUrl)->assertUnauthorized();
        $this->actingAs($this->user)->getJson($adminUrl)->assertForbidden();
        $this->get($merchantUrl)->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->actingAs($this->admin())->get($adminUrl)->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $restricted = Admin::create(['name' => 'Support', 'email' => 'support@example.test', 'password' => 'test-password', 'role' => 'support_admin', 'status' => 'active']);
        $this->actingAs($restricted)->getJson($adminUrl)->assertForbidden();
        $other = \App\Models\Business::create(['merchant_id' => $this->merchant->id, 'name' => 'Other business', 'business_code' => 'OTHER', 'verification_status' => 'pending']);
        $this->actingAs($this->user)->withHeader('X-Business-ID', $other->id)->getJson($merchantUrl)->assertNotFound();
    }

    public function test_legacy_document_is_served_privately_but_paths_and_missing_files_fail_closed(): void
    {
        Storage::fake('local'); Storage::fake('public');
        $activation = BusinessActivation::first();
        $path = 'activations/proof-address/legacy.pdf';
        Storage::disk('public')->put($path, "%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF");
        $activation->update(['proof_of_address' => '/storage/'.$path]);
        $this->actingAs($this->admin());
        $url = '/api/v1/admin/business-activations/'.$activation->id.'/documents/';
        $this->get($url.'proof_of_address?download=1')->assertOk()->assertHeader('Content-Disposition', 'attachment; filename=proof_of_address.pdf');
        $this->getJson($url.'nin')->assertNotFound();
        foreach (['activations/../../.env', 'https://external.example/file.pdf', 'activations/missing.pdf'] as $invalid) {
            $activation->update(['proof_of_address' => $invalid]);
            $this->getJson($url.'proof_of_address')->assertNotFound();
        }
    }

    public function test_pending_business_can_load_status_but_cannot_create_financial_resources(): void
    {
        $this->business->update(['verification_status' => 'under_review', 'is_verified' => false]);
        $this->actingAs($this->user)->getJson('/api/v1/merchant/profile')->assertOk();
        $this->getJson('/api/v1/merchant/activation')->assertOk();
        $this->getJson('/api/v1/wallet/balance')->assertOk();
        $this->postJson('/api/v1/virtual-accounts', [])->assertForbidden();
        $this->postJson('/api/v1/payouts/send-otp', [])->assertForbidden();
    }

    public function test_mfa_enrollment_cannot_access_admin_operations_and_codes_are_single_use(): void
    {
        config(['admin_security.require_mfa' => true]);
        $admin = $this->admin();
        $this->postJson('/api/v1/admin/auth/login', ['email' => $admin->email, 'password' => 'test-password'])->assertOk()->assertJsonPath('data.requires_mfa_setup', true);
        $this->actingAs($admin)->getJson('/api/v1/admin/business-activations')->assertForbidden();
        $setup = $this->postJson('/api/v1/admin/auth/mfa/setup', ['password' => 'test-password'])->assertOk();
        $secret = $setup->json('data.secret');
        $code = app(TotpService::class)->code($secret, intdiv(time(), 30));
        $result = $this->postJson('/api/v1/admin/auth/mfa/confirm', ['password' => 'test-password', 'code' => $code])->assertOk();
        $this->assertFalse(app(AdminMfaService::class)->verify($admin->fresh(), $code));
        $recovery = $result->json('data.recovery_codes.0');
        $this->assertTrue(app(AdminMfaService::class)->verify($admin->fresh(), null, $recovery));
        $this->assertFalse(app(AdminMfaService::class)->verify($admin->fresh(), null, $recovery));
        $this->assertStringNotContainsString($secret, $admin->fresh()->getRawOriginal('two_factor_secret'));
        $this->assertArrayNotHasKey('two_factor_secret', $admin->fresh()->toArray());
    }

    public function test_web_routes_preserve_real_errors_and_explicit_legacy_redirects(): void
    {
        foreach (['/admin-panel', '/admin-panel/dashboard', '/user/login', '/user/dashboard'] as $url) $this->get($url)->assertOk();
        foreach (['/api/v1/unknown', '/admin-panel/unknown', '/storage/activations/missing.jpg', '/unknown.jpg'] as $url) $this->get($url)->assertNotFound();
        $this->get('/admin/business-activations')->assertRedirect('/admin-panel/business-activations');
        $this->get('/merchant/activate')->assertRedirect('/user/activate');
        $this->get('/reset-password?token=test&email=a%40example.test')->assertRedirect('/user/reset-password?email=a%40example.test&token=test');
    }
}
