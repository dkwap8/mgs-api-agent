<?php
namespace Tests\Feature;
use App\Models\Transaction;
use App\Services\PlatformReportService;
use Tests\Support\PaymentTestCase;
class PlatformReportAccuracyTest extends PaymentTestCase
{
    public function test_report_keeps_exact_decimal_totals_and_does_not_invent_profit(): void
    {
        $provider = \App\Models\Provider::create(['name'=>'Report provider','slug'=>'report-provider','type'=>'virtual_account','is_active'=>true]);
        $customer = \App\Models\Customer::create(['merchant_id'=>$this->merchant->id,'business_id'=>$this->business->id,'first_name'=>'Report','last_name'=>'Fixture']);
        $va = \App\Models\VirtualAccount::create(['merchant_id'=>$this->merchant->id,'business_id'=>$this->business->id,'customer_id'=>$customer->id,'provider_id'=>$provider->id,'reference'=>'report-va','account_number'=>'9999999999','account_name'=>'Report','bank_name'=>'Fixture','status'=>'active']);
        foreach (['10.01', '20.02'] as $i => $amount) Transaction::create(['merchant_id'=>$this->merchant->id,'business_id'=>$this->business->id,'customer_id'=>$customer->id,'virtual_account_id'=>$va->id,'provider_id'=>$provider->id,'reference'=>'report-'.$i,'amount'=>$amount,'fee'=>'0.01','merchant_amount'=>$i === 0 ? '10.00' : '20.01','type'=>'collection','status'=>'success']);
        $overview = app(PlatformReportService::class)->build('all')['overview'];
        $this->assertSame('30.03', $overview['total_volume']);
        $this->assertSame('0.02', $overview['total_fees']);
        $this->assertSame('15.02', $overview['average_transaction']);
        $this->assertNull($overview['total_profit']);
        $this->assertSame('provider_costs_not_accounted', $overview['profit_status']);
    }
}
