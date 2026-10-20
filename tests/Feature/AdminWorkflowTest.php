<?php
namespace Tests\Feature;

use App\Models\{Admin, BusinessActivation, AuditLog};
use App\Services\BusinessLifecycleService;
use Tests\Support\PaymentTestCase;

class AdminWorkflowTest extends PaymentTestCase
{
    public function test_real_admin_can_approve_business_and_identity_is_not_recorded_as_user(): void
    {
        $admin = Admin::create(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'test-password', 'role' => 'super_admin', 'status' => 'active']);
        $this->actingAs($admin);
        $activation = BusinessActivation::first();
        $activation->update(['status' => 'under_review', 'kyc_status' => 'verified', 'kyc_verified_at' => now()]);
        $this->postJson('/api/v1/admin/business-activations/'.$activation->id.'/approve')->assertOk();
        $this->assertSame('approved', $activation->fresh()->status);
        $this->assertSame($admin->id, $activation->fresh()->approved_by_admin_id);
        $this->assertNull($activation->fresh()->approved_by);
        $audit = AuditLog::where('event', 'business.approved')->firstOrFail();
        $this->assertNull($audit->user_id);
        $this->assertSame($admin->id, $audit->admin_id);
    }
    public function test_business_cannot_be_approved_before_identity_verification(): void
    {
        $admin = Admin::create(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'test-password', 'role' => 'super_admin', 'status' => 'active']);
        $activation = BusinessActivation::first();
        $activation->update(['status' => 'under_review', 'kyc_status' => 'pending', 'kyc_verified_at' => null]);
        $this->actingAs($admin)->postJson('/api/v1/admin/business-activations/'.$activation->id.'/approve')->assertUnprocessable();
        $this->assertSame('under_review', $activation->fresh()->status);
    }

    public function test_activation_approval_cannot_bypass_business_suspension(): void
    {
        $admin = Admin::create(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'test-password', 'role' => 'super_admin', 'status' => 'active']);
        $activation = BusinessActivation::first();
        $activation->update(['kyc_status' => 'verified', 'kyc_verified_at' => now()]);
        $this->business->update(['verification_status' => 'suspended', 'is_verified' => false]);
        $this->actingAs($admin)->postJson('/api/v1/admin/business-activations/'.$activation->id.'/approve')->assertUnprocessable();
        $this->assertSame('suspended', $this->business->fresh()->verification_status);
    }
    public function test_finance_role_uses_seeded_permissions_without_gaining_provider_write_access(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $admin = Admin::create(['name' => 'Finance', 'email' => 'finance@example.test', 'password' => 'test-password', 'role' => 'finance_admin', 'status' => 'active']);
        $this->assertTrue($admin->hasPermission('payouts.review'));
        $this->assertFalse($admin->hasPermission('providers.manage'));
                $request = \Illuminate\Http\Request::create('/api/v1/admin/provider-transactions/1/reconcile', 'POST');
        $request->setUserResolver(fn () => $admin);
        $response = app(\App\Http\Middleware\RequirePermission::class)->handle($request, function () { $this->fail('Finance must not reconcile provider records'); }, 'auto');
        $this->assertSame(403, $response->getStatusCode());
    }
    public function test_approving_manual_payout_records_admin_without_legacy_user_foreign_key(): void
    {
        $payout = $this->payout();
        $admin = Admin::create(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'test-password', 'role' => 'super_admin', 'status' => 'active']);
        $this->actingAs($admin)->postJson('/api/v1/admin/payouts/'.$payout->id.'/approve')->assertStatus(202);
        $this->assertSame('approved', $payout->fresh()->status);
        $this->assertSame($admin->id, $payout->fresh()->approved_by_admin_id);
    }
}
