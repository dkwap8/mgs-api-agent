<?php

namespace App\Providers;

use App\Events\VirtualAccountActivated;
use App\Events\VirtualAccountRegenerated;
use App\Events\VirtualAccountSuspended;
use App\Events\WalletCredited;
use App\Events\WalletDebited;
use App\Contracts\KYCProviderInterface;
use App\Services\KYCProviderServices\HttpKYCProvider;
use App\Listeners\NotifyMerchantVirtualAccountUpdated;
use App\Listeners\NotifyWalletActivity;
use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Payout;
use App\Models\Transaction;
use App\Models\VirtualAccount;
use App\Models\Wallet;
use App\Policies\CustomerPolicy;
use App\Policies\MerchantPolicy;
use App\Policies\PayoutPolicy;
use App\Policies\TransactionPolicy;
use App\Policies\VirtualAccountPolicy;
use App\Policies\WalletPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(KYCProviderInterface::class, function () {
            return match (config('kyc.driver')) {
                'http' => app(HttpKYCProvider::class),
                default => app(HttpKYCProvider::class),
            };
        });

        $this->app->alias(KYCProviderInterface::class, 'kyc.provider');
    }

    public function boot(): void
    {
        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(Merchant::class, MerchantPolicy::class);
        Gate::policy(Payout::class, PayoutPolicy::class);
        Gate::policy(Transaction::class, TransactionPolicy::class);
        Gate::policy(VirtualAccount::class, VirtualAccountPolicy::class);
        Gate::policy(Wallet::class, WalletPolicy::class);

        RateLimiter::for('auth-sensitive', fn (Request $request) =>
            Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip())
        );
        RateLimiter::for('money', fn (Request $request) =>
            Limit::perMinute(10)->by((string) ($request->user()?->id ?? $request->ip()))
        );
        RateLimiter::for('provider-webhooks', fn (Request $request) =>
            Limit::perMinute(120)->by(($request->route('provider')?->id ?? 'unknown').'|'.$request->ip())
        );

        Event::listen(WalletCredited::class, NotifyWalletActivity::class);
        Event::listen(WalletDebited::class, NotifyWalletActivity::class);
        Event::listen(VirtualAccountActivated::class, NotifyMerchantVirtualAccountUpdated::class);
        Event::listen(VirtualAccountSuspended::class, NotifyMerchantVirtualAccountUpdated::class);
        Event::listen(VirtualAccountRegenerated::class, NotifyMerchantVirtualAccountUpdated::class);

        Request::macro('activeMerchant', function () {
            /** @var Request $this */
            return $this->attributes->get('merchant');
        });

        Request::macro('activeBusiness', function () {
            /** @var Request $this */
            return $this->attributes->get('business');
        });
    }
}
