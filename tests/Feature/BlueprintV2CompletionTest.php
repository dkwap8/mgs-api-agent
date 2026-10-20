<?php
namespace Tests\Feature;

use App\Models\{Admin,ApiKey,ApiLog,BankChangeChallenge,Business,Customer,Notification,PricingRule,Provider,Revenue,SystemSetting,Transaction,VirtualAccount,WebhookEndpoint,WebhookLog};
use App\Services\{BankChangeService,CollectionPricingService,CollectionService,MerchantWebhookService,PayoutRiskService,WebhookUrlGuard};
use App\Services\ApiKey\ApiKeyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Hash,Http};
use Illuminate\Validation\ValidationException;
use Tests\Support\PaymentTestCase;

class BlueprintV2CompletionTest extends PaymentTestCase
{
    public function test_blueprint_tiers_are_drafts_and_reseeding_preserves_operator_changes(): void
    {
        $this->seed(\Database\Seeders\BlueprintPricingSeeder::class);
        $this->assertSame(5,PricingRule::count());
        $this->assertNull(app(CollectionPricingService::class)->calculate('50000.00'));
        $rule=PricingRule::where('min_amount','10000.00')->firstOrFail();
        $rule->update(['is_active'=>true]);
        $this->seed(\Database\Seeders\BlueprintPricingSeeder::class);
        $result=app(CollectionPricingService::class)->calculate('50000.00');
        $this->assertSame('100.00',$result['charge']);
        $this->assertSame('49900.00',$result['wallet_credit']);
        $this->assertSame(5,PricingRule::count());
    }

    public function test_activated_pricing_rejects_uncovered_decimal_gap(): void
    {
        PricingRule::create(['name'=>'First tier','type'=>'collection','min_amount'=>'100.00','max_amount'=>'200.00','fee_amount'=>'5.00','fee_percentage'=>0,'is_active'=>true]);
        $this->expectException(\RuntimeException::class);
        app(CollectionPricingService::class)->calculate('200.01');
    }

    public function test_admin_cannot_activate_overlapping_collection_ranges(): void
    {
        $admin=Admin::create(['name'=>'Admin','email'=>'blueprint-admin@example.test','password'=>'test-password','role'=>'super_admin','status'=>'active']);
        $this->actingAs($admin);
        $v=['name'=>'First','type'=>'collection','min_amount'=>'100.00','max_amount'=>'200.00','fee_amount'=>'5.00','fee_percentage'=>'0.00','is_active'=>true];
        $this->postJson('/api/v1/admin/pricing',$v)->assertOk();
        $this->postJson('/api/v1/admin/pricing',array_merge($v,['name'=>'Overlap','min_amount'=>'200.00','max_amount'=>'300.00']))->assertUnprocessable();
        $this->assertSame(1,PricingRule::count());
    }

    public function test_rotated_bearer_only_key_is_scoped_and_request_replay_is_rejected(): void
    {
        $key=app(ApiKeyService::class)->rotate($this->merchant->id,$this->business->id,'live');
        $timestamp=(string)time();
        $signature=hash_hmac('sha256',$timestamp,$key['secret_key']);
        $headers=['Authorization'=>'Bearer '.$key['secret_key'],'X-MGS-Timestamp'=>$timestamp,'X-MGS-Signature'=>$signature];
        $this->getJson('/api/v1/wallet/balance',$headers)->assertOk()->assertJsonPath('data.available_balance','1000.00');
        $this->getJson('/api/v1/wallet/balance',$headers)->assertConflict();
        $this->assertSame([200,409],ApiLog::orderBy('id')->pluck('status_code')->all());
        $this->assertSame($this->business->id,ApiLog::first()->business_id);
        $this->assertArrayNotHasKey('secret_key_fingerprint',ApiKey::first()->toArray());
    }

    public function test_merchant_api_logs_and_notifications_do_not_leak_other_owners(): void
    {
        $other=Business::create(['merchant_id'=>$this->merchant->id,'name'=>'Other','business_code'=>'BIZ-LOG-OTHER','verification_status'=>'approved']);
        ApiLog::create(['merchant_id'=>$this->merchant->id,'business_id'=>$other->id,'endpoint'=>'/private','method'=>'GET','status_code'=>200]);
        ApiLog::create(['merchant_id'=>$this->merchant->id,'business_id'=>$this->business->id,'endpoint'=>'/own','method'=>'GET','status_code'=>200]);
        $this->actingAs($this->user)->getJson('/api/v1/merchant/api-logs')->assertOk()->assertJsonCount(1,'data')->assertJsonPath('data.0.endpoint','/own');
        $notice=Notification::create(['user_id'=>null,'type'=>'admin','title'=>'Private staff event','message'=>'Staff only']);
        $this->getJson('/api/v1/notifications/unread')->assertOk()->assertJsonPath('data.count',0);
        $this->postJson('/api/v1/notifications/'.$notice->id.'/read')->assertNotFound();
    }

    public function test_bank_challenge_is_bound_single_use_and_wrong_attempts_persist(): void
    {
        $c=BankChangeChallenge::create(['reference'=>(string)\Illuminate\Support\Str::uuid(),'user_id'=>$this->user->id,'business_id'=>$this->business->id,'bank_code'=>'999','account_number'=>'1234567890','otp_hash'=>Hash::make('123456'),'expires_at'=>now()->addMinutes(5)]);
        $service=app(BankChangeService::class);
        try{$service->consume($this->user,$this->business->id,$c->reference,'000000','999','1234567890');$this->fail('Wrong code accepted');}catch(ValidationException){}
        $this->assertSame(1,$c->fresh()->attempts);
        $service->consume($this->user,$this->business->id,$c->reference,'123456','999','1234567890');
        $this->assertNotNull($c->fresh()->consumed_at);
        $this->expectException(ValidationException::class);
        $service->consume($this->user,$this->business->id,$c->reference,'123456','999','1234567890');
    }

    public function test_new_bank_security_hold_prevents_reserving_money(): void
    {
        $this->bank->update(['withdrawals_available_at'=>now()->addHours(24)]);
        try{$this->payout();$this->fail('Held bank accepted');}catch(ValidationException){}
        $this->assertSame('1000.00',$this->business->wallet->fresh()->available_balance);
        $this->assertSame(0,\App\Models\Payout::count());
        $this->travel(25)->hours();
        $this->assertSame('pending',$this->payout()->status);
    }

    private function collection(): array
    {
        $provider=Provider::create(['name'=>'Blueprint Provider','slug'=>'blueprint-test','type'=>'virtual_account','health_status'=>'healthy','is_active'=>true]);
        $customer=Customer::create(['merchant_id'=>$this->merchant->id,'business_id'=>$this->business->id,'first_name'=>'Test','last_name'=>'Customer']);
        $account=VirtualAccount::create(['merchant_id'=>$this->merchant->id,'business_id'=>$this->business->id,'customer_id'=>$customer->id,'provider_id'=>$provider->id,'reference'=>'VA-BLUEPRINT','account_number'=>'9999999999','account_name'=>'Test Customer','bank_name'=>'Fixture Bank','status'=>'active']);
        $transaction=Transaction::create(['merchant_id'=>$this->merchant->id,'business_id'=>$this->business->id,'customer_id'=>$customer->id,'virtual_account_id'=>$account->id,'provider_id'=>$provider->id,'reference'=>'COL-BLUEPRINT','provider_reference'=>'UPSTREAM-BLUEPRINT','amount'=>'100.00','fee'=>'5.00','merchant_amount'=>'95.00','type'=>'collection','status'=>'pending']);
        WebhookEndpoint::create(['merchant_id'=>$this->merchant->id,'business_id'=>$this->business->id,'url'=>'https://merchant.example.test/hook','secret'=>'fixture-secret','events'=>['payment.success','collection.success','payment.failed'],'environment'=>'live','status'=>'active']);
        return [$transaction,$provider];
    }

    public function test_dashboard_aggregates_more_than_the_recent_activity_limit(): void
    {
        [$transaction,$provider]=$this->collection();
        $transaction->update(['status'=>'success','confirmed_at'=>now()]);
        for($i=0;$i<105;$i++) Transaction::create([
            'merchant_id'=>$this->merchant->id,'business_id'=>$this->business->id,'customer_id'=>$transaction->customer_id,
            'virtual_account_id'=>$transaction->virtual_account_id,'provider_id'=>$provider->id,
            'reference'=>'COL-AGG-'.$i,'provider_reference'=>'UPSTREAM-AGG-'.$i,
            'amount'=>'1.00','fee'=>'0.00','merchant_amount'=>'1.00','type'=>'collection','status'=>'success','confirmed_at'=>now(),
        ]);
        $this->actingAs($this->user)->getJson('/api/v1/merchant/dashboard/summary?period=today')
            ->assertOk()->assertJsonPath('data.total_transactions',106)->assertJsonPath('data.collections','205.00')
            ->assertJsonPath('data.today_net_collections','200.00');
    }

    public function test_duplicate_payment_success_records_one_fee_and_one_canonical_event(): void
    {
        [$t,$p]=$this->collection();$result=['status'=>'success','amount'=>'100.00','provider_reference'=>$t->provider_reference];
        app(CollectionService::class)->settleVerified($t,$p,$result);
        app(CollectionService::class)->settleVerified($t,$p,$result);
        $this->assertSame(1,Revenue::count());$this->assertSame('5.00',Revenue::first()->fee);
        $this->assertNull(Revenue::first()->provider_cost);
        $this->assertSame(1,WebhookLog::count());$this->assertSame('payment.success',WebhookLog::first()->event);
        $this->assertSame('1095.00',$this->business->wallet->fresh()->available_balance);
    }

    public function test_failed_callback_cannot_credit_even_if_verification_disagrees(): void
    {
        [$t,$p]=$this->collection();$t->update(['metadata'=>['webhook_status'=>'failed']]);
        try { app(CollectionService::class)->settleVerified($t,$p,['status'=>'success','amount'=>'100.00','provider_reference'=>$t->provider_reference]); $this->fail('Contradictory callback credited'); }
        catch(\RuntimeException){}
        $this->assertSame('1000.00',$this->business->wallet->fresh()->available_balance);
        $this->assertSame(0,Revenue::count());$this->assertSame('pending',$t->fresh()->status);
    }

    public function test_failed_payment_delivers_failure_without_wallet_credit_or_revenue(): void
    {
        [$t,$p]=$this->collection();
        app(CollectionService::class)->settleVerified($t,$p,['status'=>'failed','provider_reference'=>$t->provider_reference,'amount'=>'100.00']);
        $this->assertSame(0,Revenue::count());$this->assertSame('1000.00',$this->business->wallet->fresh()->available_balance);
        $this->assertSame('payment.failed',WebhookLog::first()->event);
        $this->mock(WebhookUrlGuard::class)->shouldReceive('options')->once()->andReturn([]);
        Http::fake(['*'=>Http::response('',200)]);
        $result=app(MerchantWebhookService::class)->deliverLog(WebhookLog::first());
        $this->assertSame('delivered',$result->status);
    }

    public function test_provider_credentials_are_encrypted_on_new_writes_and_legacy_reads_work(): void
    {
        $p=Provider::create(['name'=>'Encrypted','slug'=>'encrypted','type'=>'virtual_account','config'=>['secret_key'=>'fixture-provider-secret']]);
        $raw=DB::table('providers')->where('id',$p->id)->value('config');
        $this->assertStringNotContainsString('fixture-provider-secret',$raw);
        $this->assertSame('fixture-provider-secret',$p->fresh()->config['secret_key']);
        DB::table('providers')->where('id',$p->id)->update(['config'=>json_encode(['secret_key'=>'legacy-fixture'])]);
        $this->assertSame('legacy-fixture',$p->fresh()->config['secret_key']);
    }

    public function test_maintenance_blocks_merchants_but_keeps_staff_recovery_available(): void
    {
        SystemSetting::setValue('maintenance_mode',true);
        $this->actingAs($this->user)->getJson('/api/v1/wallet/balance')->assertStatus(503);
        $admin=Admin::create(['name'=>'Admin','email'=>'maintenance-admin@example.test','password'=>'test-password','role'=>'super_admin','status'=>'active']);
        $this->actingAs($admin)->getJson('/api/v1/admin/settings')->assertOk();
    }

    public function test_untrusted_device_requires_review_and_trusted_matching_ip_clears_contextual_risk(): void
    {
        $request=Request::create('/payout','POST');$request->headers->set('X-Device-ID','fixture-device');
        $this->assertNotNull(app(PayoutRiskService::class)->reason($this->user,$request));
        $d=app(\App\Services\AccountSecurityService::class)->registerDevice($request,$this->user);$d->update(['trusted'=>true]);
        $this->assertNull(app(PayoutRiskService::class)->reason($this->user,$request));
        $request->server->set('REMOTE_ADDR','198.51.100.2');
        $this->assertNotNull(app(PayoutRiskService::class)->reason($this->user,$request));
    }
}
