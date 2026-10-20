<?php
namespace Tests\Feature;

use App\Models\User;
use App\Services\{TotpService, AccountSecurityService, UserMfaService};
use Illuminate\Support\Facades\{DB, Hash};
use Tests\Support\PaymentTestCase;

class UserSecurityCompletionTest extends PaymentTestCase
{
    public function test_user_authenticator_setup_requires_password_and_is_encrypted_confirmed_and_single_use(): void
    {
        $this->actingAs($this->user)->postJson('/api/v1/auth/setup-two-factor', ['password' => 'wrong'])->assertUnprocessable();
        $secret = $this->postJson('/api/v1/auth/setup-two-factor', ['password' => 'test-password'])->assertOk()->json('data.secret');
        $this->assertNotSame($secret, DB::table('users')->where('id', $this->user->id)->value('two_factor_pending_secret'));
        $this->assertNull($this->user->fresh()->two_factor_confirmed_at);
        $code = app(TotpService::class)->code($secret, intdiv(time(), 30));
        $codes = $this->postJson('/api/v1/auth/verify-two-factor', ['password' => 'test-password', 'code' => $code])->assertOk()->json('data.recovery_codes');
        $this->assertCount(8, $codes);
        $this->assertNotSame($secret, DB::table('users')->where('id', $this->user->id)->value('two_factor_secret'));
        $this->assertSame($secret, $this->user->fresh()->two_factor_secret);
        $this->assertFalse(app(UserMfaService::class)->verify($this->user, $code));
        $this->assertTrue(app(UserMfaService::class)->verify($this->user, null, $codes[0]));
        $this->assertFalse(app(UserMfaService::class)->verify($this->user, null, $codes[0]));
        $this->postJson('/api/v1/auth/setup-two-factor', ['password' => 'test-password'])->assertUnprocessable();
        $this->getJson('/api/v1/auth/me')->assertJsonMissingPath('data.user.two_factor_secret')->assertJsonMissingPath('data.user.two_factor_pending_secret');
    }

    public function test_legacy_plaintext_totp_secret_is_read_without_rewriting_or_rotating_it(): void
    {
        $secret = 'JBSWY3DPEHPK3PXP';
        DB::table('users')->where('id', $this->user->id)->update(['two_factor_secret' => $secret]);
        $this->assertSame($secret, $this->user->fresh()->two_factor_secret);
        $this->assertSame($secret, DB::table('users')->where('id', $this->user->id)->value('two_factor_secret'));
    }

    public function test_password_reset_expiry_and_single_consumption_revoke_old_tokens(): void
    {
        $token = 'reset-fixture-secret';
        $payload = ['email' => $this->user->email, 'token' => $token, 'password' => 'new-test-password', 'password_confirmation' => 'new-test-password'];
        $this->user->createToken('old-session');
        DB::table('password_reset_tokens')->insert(['email' => $this->user->email, 'token' => Hash::make($token), 'created_at' => now()->subMinutes(31)]);
        $this->postJson('/api/v1/auth/reset-password', $payload)->assertUnprocessable();
        $this->assertTrue(Hash::check('test-password', $this->user->fresh()->password));
        DB::table('password_reset_tokens')->where('email', $this->user->email)->update(['created_at' => now()]);
        $this->postJson('/api/v1/auth/reset-password', $payload)->assertOk();
        $this->assertTrue(Hash::check('new-test-password', $this->user->fresh()->password));
        $this->assertSame(0, $this->user->tokens()->count());
        $this->travel(61)->seconds();
        $this->postJson('/api/v1/auth/reset-password', $payload)->assertUnprocessable();
    }

    public function test_device_revocation_is_owned_and_revokes_its_tokens(): void
    {
        $device = $this->user->devices()->create(['device_id' => 'own-fixture', 'device_name' => 'Fixture device', 'last_seen_at' => now()]);
        $other = User::create(['uuid' => (string) \Illuminate\Support\Str::uuid(), 'name' => 'Other', 'email' => 'other-device@example.test', 'password' => Hash::make('test-password'), 'status' => 'active']);
        $otherDevice = $other->devices()->create(['device_id' => 'other-fixture', 'device_name' => 'Private device']);
        $this->user->createToken('device:'.$device->id);
        $this->actingAs($this->user)->deleteJson('/api/v1/auth/devices/'.$otherDevice->id)->assertNotFound();
        $this->deleteJson('/api/v1/auth/devices/'.$device->id)->assertOk();
        $this->assertNotNull($device->fresh()->revoked_at);
        $this->assertSame(0, $this->user->tokens()->where('name', 'device:'.$device->id)->count());
    }
}
