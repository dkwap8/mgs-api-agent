<?php
namespace App\Http\Controllers\Api\Merchant;
use App\Http\Controllers\Controller;
use App\Models\{ApiKey,Customer,Payout,Transaction,VirtualAccount};
use App\Support\{ApiResponse,Money};
use Illuminate\Http\Request;
class MerchantDashboardController extends Controller
{
    public function summary(Request $request) {
        $v=$request->validate(['period'=>['nullable','in:today,yesterday,7days,30days,all']]);
        $period=$v['period']??'today';$business=$request->activeBusiness();$merchant=$request->activeMerchant();
        [$from,$to]=match($period){'today'=>[today(),now()->endOfDay()],'yesterday'=>[today()->subDay(),today()->subDay()->endOfDay()],
            '7days'=>[today()->subDays(6),now()->endOfDay()],'30days'=>[today()->subDays(29),now()->endOfDay()],default=>[null,null]};
        $base=Transaction::query()->where('merchant_id',$merchant->id)->where('business_id',$business->id)->where('type','collection');
        $q=clone $base;if($from)$q->whereBetween('created_at',[$from,$to]);
        $count=(clone $q)->count();$success=(clone $q)->successful()->count();
        $payouts=Payout::query()->where('merchant_id',$merchant->id)->where('business_id',$business->id)->whereIn('status',Payout::SUCCESSFUL_STATUSES);
        if($from)$payouts->whereBetween('created_at',[$from,$to]);
        return ApiResponse::success(['period'=>$period,'total_transactions'=>$count,'successful_transactions'=>$success,
            'success_rate'=>$count?round($success/$count*100,2):0,
            'collections'=>Money::format((string)(clone $q)->successful()->sum('amount')),
            'payouts'=>Money::format((string)$payouts->sum('amount')),
            'today_net_collections'=>Money::format((string)(clone $base)->successful()->whereDate('confirmed_at',today())->sum('merchant_amount')),
            'today_transactions'=>(clone $base)->whereDate('created_at',today())->count(),
            'total_customers'=>Customer::where('merchant_id',$merchant->id)->where('business_id',$business->id)->count(),
            'total_virtual_accounts'=>VirtualAccount::where('merchant_id',$merchant->id)->where('business_id',$business->id)->count(),
            'api_status'=>$business->isApproved() && $merchant->isApproved() && $merchant->live_access && ApiKey::where('business_id',$business->id)->where('environment','live')->where('status','active')->whereNull('revoked_at')->exists()?'enabled':'disabled',
        ]);
    }
}
