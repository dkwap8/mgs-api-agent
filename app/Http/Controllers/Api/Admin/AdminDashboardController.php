<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Merchant, Business, BusinessActivation, Customer, Wallet, Payout, Transaction};
use App\Support\Money;

class AdminDashboardController extends Controller
{
    public function summary()
    {
        $pending = Payout::whereIn('status', [Payout::STATUS_PENDING, Payout::STATUS_APPROVED, Payout::STATUS_PROCESSING]);
        return response()->json(['success' => true, 'data' => [
            'total_merchants' => Merchant::count(),
            'total_businesses' => Business::count(),
            'pending_activations' => BusinessActivation::whereIn('status', ['pending', 'under_review'])->count(),
            'approved_businesses' => Business::where('verification_status', Business::STATUS_APPROVED)->count(),
            'total_transactions' => Transaction::count(),
            'total_customers' => Customer::count(),
            'pending_payouts' => Payout::where('status', Payout::STATUS_PENDING)->count(),
            'successful_transactions' => Transaction::successful()->count(),
            'total_transaction_value' => Money::format(Transaction::sum('amount')),
            'today_revenue' => Money::format(Transaction::successful()->whereDate('created_at', today())->sum('fee')),
            'total_wallet_balance' => Money::format(Wallet::whereNotNull('business_id')->where('currency', 'NGN')->sum('available_balance')),
            'pending_settlements' => Money::format($pending->sum('amount')),
            'health_status' => ($health = \Illuminate\Support\Facades\Cache::get('platform:health')) ? ($health['healthy'] ? 'healthy' : 'degraded') : 'not_monitored',
            'health' => $health,
            'failed_webhooks' => \App\Models\WebhookLog::where('status','failed')->count(),
            'provider_statuses' => \App\Models\Provider::query()->select(['id','name','is_active','health_status'])->get(),
        ]]);
    }
}
