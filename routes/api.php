<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\WalletController;
use App\Http\Controllers\Api\V1\VirtualAccountController;
use App\Http\Controllers\Api\V1\PayoutController;
use App\Http\Controllers\Api\V1\WithdrawalOtpController;
use App\Http\Controllers\Api\V1\AnalyticsController;
use App\Http\Controllers\Api\V1\TransactionController;
use App\Http\Controllers\Api\Merchant\ApiKeyController as MerchantApiKeyController;
use App\Http\Controllers\Api\Merchant\MerchantProfileController;
use App\Http\Controllers\Api\V1\BusinessController;
use App\Http\Controllers\Api\Merchant\MerchantActivationController;
use App\Http\Controllers\Api\Admin\AdminMerchantController;
use App\Http\Controllers\Api\V1\SupportController;
use App\Http\Controllers\Api\Admin\AdminTransactionController;
use App\Http\Controllers\Api\Admin\AdminProviderController;
use App\Http\Controllers\Api\Admin\AdminQueueController;
use App\Http\Controllers\Api\Admin\AdminPayoutController;
use App\Http\Controllers\Api\Admin\AdminReportController;
use App\Http\Controllers\Api\Admin\AdminSupportController;
use App\Http\Controllers\Api\Admin\AdminSettingController;
use App\Http\Controllers\Api\Admin\AdminNotificationController;
use App\Http\Controllers\Api\Admin\AdminCustomerController;
use App\Http\Controllers\Api\Admin\AdminBusinessActivationController;
use App\Http\Controllers\Api\Admin\AdminBusinessController;
use App\Http\Controllers\Api\Admin\AdminActivityLogController;
use App\Http\Controllers\Api\Admin\AdminVirtualAccountController;
use App\Http\Controllers\Api\Admin\AdminWithdrawalSettingController;
use App\Http\Controllers\Api\V1\WebhookController;
use App\Http\Controllers\Api\Merchant\MerchantWebhookController;
use App\Http\Controllers\Api\Admin\AdminAuthController;

// ============================================================
// ADMIN AUTHENTICATION
// ============================================================
Route::prefix('v1/admin/auth')
        ->middleware('throttle:auth-sensitive')
        ->group(function () {
            Route::post('/login', [
                AdminAuthController::class,
                'login',
        ]);
    });

Route::prefix('v1/admin/auth')
    ->middleware([
        'auth:sanctum',
        'admin',
    ])
    ->group(function () {
        Route::post('/mfa/setup', [\App\Http\Controllers\Api\Admin\AdminMfaController::class, 'setup'])->middleware('throttle:auth-sensitive');
        Route::post('/mfa/confirm', [\App\Http\Controllers\Api\Admin\AdminMfaController::class, 'confirm'])->middleware('throttle:auth-sensitive');
        Route::post('/mfa/recovery-codes', [\App\Http\Controllers\Api\Admin\AdminMfaController::class, 'regenerate'])->middleware('throttle:auth-sensitive');
        Route::get('/me', [
            AdminAuthController::class,
            'me',
        ]);

        Route::post('/logout', [
            AdminAuthController::class,
            'logout',
        ]);

        Route::post('/logout-all', [
            AdminAuthController::class,
            'logoutAll',
        ]);
    });

// ============================================================
// PUBLIC (guest) AUTH ROUTES
// ============================================================
Route::prefix('v1')->middleware('throttle:30,1')->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register'])->name('api.v1.auth.register');

    // Registration OTP
    Route::post('/auth/register/send-otp', [AuthController::class, 'sendRegistrationOtp']);
    Route::post('/auth/register/resend-otp', [AuthController::class, 'resendRegistrationOtp']);
    Route::post('/auth/register/verify-otp', [AuthController::class, 'verifyRegistrationOtp']);

    Route::post('/auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:auth-sensitive')
        ->name('api.v1.auth.login');
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])
        ->middleware('throttle:auth-sensitive')
        ->name('api.v1.auth.forgot-password');
    Route::post('/auth/reset-password', [AuthController::class, 'resetPassword'])
        ->middleware('throttle:5,1')
        ->name('api.v1.auth.reset-password');
});

// ============================================================
// AUTHENTICATED, ACCOUNT-LEVEL ROUTES (not scoped to a single
// business — a user can own many, so these run before/without a
// resolved active merchant)
// ============================================================
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::middleware('user')->group(function () {
    Route::get('/notifications/unread', [\App\Http\Controllers\Api\Merchant\MerchantNotificationController::class,'unread']);
    Route::post('/notifications/read-all', [\App\Http\Controllers\Api\Merchant\MerchantNotificationController::class,'readAll']);
    Route::post('/notifications/{id}/read', [\App\Http\Controllers\Api\Merchant\MerchantNotificationController::class,'read'])->whereNumber('id');
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/logout-all', [AuthController::class, 'logoutAll'])->middleware('throttle:auth-sensitive');
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/change-password', [AuthController::class, 'changePassword'])->middleware('throttle:auth-sensitive');
    Route::post('/auth/setup-two-factor', [AuthController::class, 'setupTwoFactor'])->middleware('throttle:auth-sensitive');
    Route::post('/auth/verify-two-factor', [AuthController::class, 'verifyTwoFactor'])->middleware('throttle:auth-sensitive');
    Route::post('/auth/two-factor/recovery-codes', [AuthController::class, 'regenerateRecoveryCodes'])->middleware('throttle:auth-sensitive');
    Route::delete('/auth/two-factor', [AuthController::class, 'disableTwoFactor'])->middleware('throttle:auth-sensitive');
    Route::get('/auth/devices', [AuthController::class, 'devices']);
    Route::delete('/auth/devices/{device}', [AuthController::class, 'revokeDevice'])->whereNumber('device');
    Route::post('/auth/setup-withdrawal-pin', [AuthController::class, 'setupWithdrawalPin'])->middleware('throttle:auth-sensitive');

    // Business switcher: list every business the user owns, create a new
    // one (unlimited businesses per user), and switch which one is active.
    Route::get('/merchant/businesses', [BusinessController::class, 'index']);
    Route::post('/merchant/businesses', [BusinessController::class, 'store']);
    Route::post('/merchant/switch', [BusinessController::class, 'switch']);

    // Support Routes (not merchant-scoped: also usable pre-activation)
    Route::get('/support/tickets', [SupportController::class, 'index']);
    Route::post('/support/tickets', [SupportController::class, 'store']);
    Route::get('/support/tickets/{id}', [SupportController::class, 'show']);
    Route::post('/support/tickets/{id}/reply', [SupportController::class, 'reply']);
    Route::post('/support/tickets/{id}/close', [SupportController::class, 'close']);
    Route::get('/support/stats', [SupportController::class, 'stats']);
    });

    // ============================================================
    // ADMIN ROUTES (platform staff, not merchant-scoped)
    // ============================================================
    Route::middleware(['admin', 'permission:auto', 'throttle:120,1'])->group(function () {
    Route::get('/admin/dashboard/summary', [\App\Http\Controllers\Api\Admin\AdminDashboardController::class, 'summary'])->middleware('permission:dashboard.view');
    Route::get('/admin/merchants', [AdminMerchantController::class, 'index'])->middleware('permission:merchants.view');
    Route::get('/admin/merchants/{merchant}', [AdminMerchantController::class, 'show']);
    Route::post('/admin/merchants/{merchant}/approve', [AdminMerchantController::class, 'approve']);
    Route::post('/admin/merchants/{merchant}/reject', [AdminMerchantController::class, 'reject']);
    Route::post('/admin/merchants/{merchant}/suspend', [AdminMerchantController::class, 'suspend']);

    // Admin Transaction Routes
    Route::get('/admin/transactions', [AdminTransactionController::class, 'index'])->middleware('permission:transactions.view');
    Route::get('/admin/transactions/stats', [AdminTransactionController::class, 'stats']);
    Route::get('/admin/transactions/{id}', [AdminTransactionController::class, 'show']);

    // Admin Payout Routes
    Route::get('/admin/payouts', [AdminPayoutController::class, 'index'])->middleware('permission:payouts.view');
    Route::get('/admin/payouts/stats', [AdminPayoutController::class, 'stats']);
    Route::get('/admin/payouts/{id}', [AdminPayoutController::class, 'show']);
    Route::post('/admin/payouts/{id}/approve', [AdminPayoutController::class, 'approve']);
    Route::post('/admin/payouts/{id}/reject', [AdminPayoutController::class, 'reject']);
    Route::post('/admin/payouts/{id}/retry', [AdminPayoutController::class, 'retryProcessing'])->whereNumber('id');


    Route::get('/admin/virtual-accounts', [AdminVirtualAccountController::class, 'index']);
    Route::post('/admin/virtual-accounts/{id}/regenerate', [AdminVirtualAccountController::class, 'regenerate'])->whereNumber('id');
    Route::post('/admin/virtual-accounts/{id}/activate', [AdminVirtualAccountController::class, 'activate'])->whereNumber('id');
    Route::post('/admin/virtual-accounts/{id}/suspend', [AdminVirtualAccountController::class, 'suspend'])->whereNumber('id');
    Route::delete('/admin/virtual-accounts/{id}', [AdminVirtualAccountController::class, 'destroy'])->whereNumber('id');


    Route::get('/admin/queue/failed', [AdminQueueController::class, 'failed']);
    Route::post('/admin/queue/failed/{uuid}/retry', [AdminQueueController::class, 'retry'])->whereUuid('uuid');
    Route::delete('/admin/queue/failed/{uuid}', [AdminQueueController::class, 'forget'])->whereUuid('uuid');

    Route::get('/admin/kyc-requests', [\App\Http\Controllers\Api\Admin\AdminKycController::class,'index']);
    Route::post('/admin/kyc-requests/{id}/review', [\App\Http\Controllers\Api\Admin\AdminKycController::class,'review'])->whereNumber('id');
    Route::get('/admin/audit-logs', [\App\Http\Controllers\Api\Admin\AdminAuditController::class,'index']);
    Route::get('/admin/webhook-logs', [\App\Http\Controllers\Api\Admin\AdminWebhookLogController::class,'index']);
    Route::get('/admin/pricing', [\App\Http\Controllers\Api\Admin\AdminPricingController::class, 'index']);
    Route::post('/admin/pricing', [\App\Http\Controllers\Api\Admin\AdminPricingController::class, 'store']);
    Route::put('/admin/pricing/{pricingRule}', [\App\Http\Controllers\Api\Admin\AdminPricingController::class, 'update']);
    Route::get('/admin/reports', [AdminReportController::class, 'index']);

    // Admin Support Routes
    Route::get('/admin/support', [AdminSupportController::class, 'index']);
    Route::get('/admin/support/stats', [AdminSupportController::class, 'stats']);
    Route::get('/admin/support/{id}', [AdminSupportController::class, 'show']);

    Route::post('/admin/support/{id}/reply', [AdminSupportController::class, 'reply']);
    Route::post('/admin/support/{id}/resolve', [AdminSupportController::class, 'resolve']);
    Route::post('/admin/support/{id}/reopen', [AdminSupportController::class, 'reopen']);
    Route::post('/admin/support/{id}/close', [AdminSupportController::class, 'close']);

    // Admin Settings Routes
    Route::get('/admin/settings', [AdminSettingController::class, 'index']);
    Route::post('/admin/settings', [AdminSettingController::class, 'update']);

    Route::get(
        '/admin/notifications',
        [AdminNotificationController::class, 'index']
    );

    Route::get(
        '/admin/notifications/unread',
        [AdminNotificationController::class, 'unread']
    );

    Route::post(
        '/admin/notifications/read-all',
        [AdminNotificationController::class, 'markAllRead']
    );

    Route::post(
        '/admin/notifications/{id}/read',
        [AdminNotificationController::class, 'markRead']
    )->whereNumber('id');

    Route::delete(
        '/admin/notifications/{id}',
        [AdminNotificationController::class, 'destroy']
    )->whereNumber('id');


    Route::get('/admin/notification-deliveries', [AdminNotificationController::class, 'deliveries']);
    Route::post('/admin/notification-deliveries/{id}/retry', [AdminNotificationController::class, 'retryDelivery'])->whereNumber('id');
    Route::delete('/admin/notification-deliveries/{id}', [AdminNotificationController::class, 'forgetDelivery'])->whereNumber('id');

    Route::get('/admin/activity-logs', [AdminActivityLogController::class, 'index'])->middleware('permission:audit.view');

    Route::get('/admin/customers', [AdminCustomerController::class, 'index']);
    Route::get('/admin/customers/stats', [AdminCustomerController::class, 'stats']);
    Route::get('/admin/customers/{id}', [AdminCustomerController::class, 'show']);
    Route::post('/admin/customers/{id}/block', [AdminCustomerController::class, 'block']);
    Route::post('/admin/customers/{id}/unblock', [AdminCustomerController::class, 'unblock']);

    Route::get('/admin/business-activations', [AdminBusinessActivationController::class, 'index'])->middleware('permission:businesses.view');
    Route::get('/admin/business-activations/{id}/documents/{field}', [\App\Http\Controllers\Api\ActivationDocumentController::class, 'admin'])->whereNumber('id')->middleware('permission:businesses.view');
    Route::get('/admin/business-activations/{id}', [AdminBusinessActivationController::class, 'show']);
    Route::post('/admin/business-activations/{id}/approve', [AdminBusinessActivationController::class, 'approve']);
    Route::post('/admin/business-activations/{id}/reject', [AdminBusinessActivationController::class, 'reject']);
    Route::post('/admin/businesses/{business}/suspend', [AdminBusinessController::class, 'suspend'])->whereNumber('business');
    Route::post('/admin/businesses/{business}/reactivate', [AdminBusinessController::class, 'reactivate'])->whereNumber('business');

    Route::get(
    '/admin/withdrawal-settings',
    [AdminWithdrawalSettingController::class, 'index']
    );

    Route::post(
        '/admin/withdrawal-settings',
        [AdminWithdrawalSettingController::class, 'store']
    );

    Route::post(
        '/admin/withdrawal-settings/preview',
        [AdminWithdrawalSettingController::class, 'preview']
    );

    Route::get(
    '/admin/withdrawal-settings/{withdrawalSetting}',
    [AdminWithdrawalSettingController::class, 'show']
    );

    Route::put(
    '/admin/withdrawal-settings/{withdrawalSetting}',
    [AdminWithdrawalSettingController::class, 'update']
    );

    Route::post(
        '/admin/withdrawal-settings/{withdrawalSetting}/toggle',
        [AdminWithdrawalSettingController::class, 'toggle']
    );

    Route::delete(
        '/admin/withdrawal-settings/{withdrawalSetting}',
        [AdminWithdrawalSettingController::class, 'destroy']
    );

    Route::get(
    '/admin/providers',
    [AdminProviderController::class, 'index']
    );

    Route::get(
    '/admin/providers/stats',
    [AdminProviderController::class, 'stats']
    );

    Route::post(
    '/admin/providers',
    [AdminProviderController::class, 'store']
    );

    Route::get(
    '/admin/providers/{provider}',
    [AdminProviderController::class, 'show']
    );

    Route::put(
    '/admin/providers/{provider}',
    [AdminProviderController::class, 'update']
    );

    Route::post(
    '/admin/providers/{provider}/toggle',
    [AdminProviderController::class, 'toggle']
    );

    Route::post(
    '/admin/providers/{provider}/test',
    [AdminProviderController::class, 'test']
    );

    Route::get(
    '/admin/providers/{provider}/health-history',
    [AdminProviderController::class, 'healthHistory']
    );

    Route::post(
    '/admin/providers/{provider}/reset-circuit',
    [AdminProviderController::class, 'resetCircuit']
    );

    Route::get('/admin/providers/{provider}/performance', [AdminProviderController::class, 'performance']);
    Route::get('/admin/providers/{provider}/incidents', [AdminProviderController::class, 'incidents']);
    Route::post('/admin/providers/{provider}/incidents/{incident}/resolve', [AdminProviderController::class, 'resolveIncident'])->whereNumber('incident');
    Route::post('/admin/provider-transactions/{transaction}/reconcile', [AdminProviderController::class, 'reconcileTransaction'])->whereNumber('transaction');

    Route::get(
    '/admin/providers/{provider}/funding-charge',
    [AdminProviderController::class, 'fundingCharge']
    );

    Route::put(
    '/admin/providers/{provider}/funding-charge',
    [AdminProviderController::class, 'updateFundingCharge']
    );

    Route::post(
    '/admin/providers/{provider}/funding-charge/preview',
    [AdminProviderController::class, 'previewFundingCharge']
    );

    Route::delete(
    '/admin/providers/{provider}',
    [AdminProviderController::class, 'destroy']

    );


    });
});

// ============================================================
// PROVIDER WEBHOOK ROUTES
// Public endpoints protected by provider signatures.
// ============================================================
Route::prefix('v1')
    ->middleware('throttle:provider-webhooks')
    ->group(function () {
        Route::post(
            '/webhooks/providers/{provider:slug}',
            [WebhookController::class, 'provider']
        )->name('api.v1.webhooks.providers');
    });
// ============================================================
// BUSINESS-SCOPED ROUTES
// Everything below only ever sees ONE business at a time: the one
// resolved by EnsureActiveMerchant (merchant.context). Controllers must
// read it via $request->activeMerchant() — never $request->user()->merchant.
// ============================================================
Route::prefix('v1')->middleware(['merchant.access'])->group(function () {
    Route::post('/customers/verify', [CustomerController::class, 'verify'])->middleware(['business.active', 'idempotency']);
    Route::get('/merchant/dashboard/summary', [\App\Http\Controllers\Api\Merchant\MerchantDashboardController::class,'summary']);
    Route::get('/analytics', [AnalyticsController::class, 'index']);

    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::get('/transactions/verify/{reference}', [TransactionController::class, 'show'])->where('reference', '[A-Za-z0-9\-]+');
    Route::post('/merchant/bank-change/send-otp', [\App\Http\Controllers\Api\Merchant\BankChangeController::class, 'send'])->middleware(['business.active','throttle:5,10']);
    Route::get('/merchant/api-logs', [\App\Http\Controllers\Api\Merchant\ApiLogController::class, 'index']);
    Route::get('/transactions/summary', [TransactionController::class, 'summary']);
    Route::get('/transactions/{reference}', [TransactionController::class, 'show'])->where('reference', '[A-Za-z0-9\-]+');


    Route::get('/merchant/profile', [MerchantProfileController::class, 'show']);
    Route::put('/merchant/profile', [MerchantProfileController::class, 'update']);

    Route::middleware('throttle:20,1')->group(function () {
        Route::get('/merchant/api-keys', [MerchantApiKeyController::class, 'index']);
        Route::post('/merchant/api-keys', [MerchantApiKeyController::class, 'generate'])
            ->middleware('throttle:5,1');
        Route::post('/merchant/api-keys/{id}/reveal', [MerchantApiKeyController::class, 'reveal'])
            ->whereNumber('id')
            ->middleware('throttle:5,1');
        Route::delete('/merchant/api-keys/{id}', [MerchantApiKeyController::class, 'revoke'])
            ->whereNumber('id')
            ->middleware('throttle:5,1');
    });

    Route::get('/merchant/activation', [MerchantActivationController::class, 'show']);
    Route::get('/merchant/activations/{id}/documents/{field}', [\App\Http\Controllers\Api\ActivationDocumentController::class, 'merchant'])->whereNumber('id');
    Route::post(
        '/merchant/activation',
        [MerchantActivationController::class, 'submit']
    )->middleware('throttle:3,10');

    Route::get(
        '/wallet/balance',
        [WalletController::class, 'balance']
    );
    Route::get('/wallet/summary', [WalletController::class, 'summary']);
    Route::get('/wallet/transactions', [WalletController::class, 'transactions']);
    Route::get('/wallet/transactions/{reference}', [WalletController::class, 'transactionDetails']);

    Route::get('/customers', [CustomerController::class, 'index'])->middleware('business.active');
    Route::post('/customers', [CustomerController::class, 'store'])->middleware(['business.active', 'idempotency']);
    Route::get('/customers/{id}', [CustomerController::class, 'show'])->whereNumber('id')->middleware('business.active');
    Route::put('/customers/{id}', [CustomerController::class, 'update'])->whereNumber('id')->middleware('business.active');
    Route::patch('/customers/{id}', [CustomerController::class, 'update'])->whereNumber('id')->middleware('business.active');
    Route::post('/customers/{id}/verify-bvn', [CustomerController::class, 'verifyBVN'])->whereNumber('id')->middleware('business.active');
    Route::post('/customers/{id}/verify-nin', [CustomerController::class, 'verifyNIN'])->whereNumber('id')->middleware('business.active');
    Route::get('/customers/{id}/kyc-status', [CustomerController::class, 'kycStatus'])->whereNumber('id');

    Route::get('/virtual-accounts', [VirtualAccountController::class, 'index']);
    Route::post('/virtual-accounts', [VirtualAccountController::class, 'store'])->middleware(['business.active', 'idempotency']);
    Route::get('/virtual-accounts/{id}', [VirtualAccountController::class, 'show'])->whereNumber('id');
    Route::post('/virtual-accounts/{id}/deactivate', [VirtualAccountController::class, 'deactivate'])->whereNumber('id')->middleware('business.active');
    Route::get('/virtual-accounts/{id}/transactions', [VirtualAccountController::class, 'getTransactions'])->whereNumber('id');

    Route::get('/payouts', [PayoutController::class, 'index']);
    Route::post('/payouts', [WithdrawalOtpController::class, 'send'])
        ->middleware(['business.active', 'payout.security', 'idempotency', 'throttle:money']);
    Route::post('/payouts/preview', [PayoutController::class, 'preview'])
        ->middleware('throttle:20,1');
    Route::post('/payouts/send-otp', [WithdrawalOtpController::class, 'send'])
        ->middleware(['business.active', 'payout.security', 'throttle:5,1']);
    Route::post('/payouts/verify-otp', [WithdrawalOtpController::class, 'verify'])
        ->middleware(['business.active', 'payout.security', 'throttle:10,1']);
    Route::get('/payouts/{reference}', [PayoutController::class, 'show']);
    Route::get('/bank-accounts', [PayoutController::class, 'bankAccounts']);
    Route::post('/bank-accounts', [PayoutController::class, 'addBankAccount'])->middleware(['business.active', 'idempotency']);
    Route::delete('/bank-accounts/{id}', [PayoutController::class, 'removeBankAccount'])->whereNumber('id');
    Route::post('/bank-accounts/verify', [PayoutController::class, 'verifyBankAccount'])->middleware(['business.active', 'throttle:money']);

    //+===========================================================
    // MERCHANT WEBHOOK ROUTES
    Route::get(
        '/webhooks/endpoint',
        [MerchantWebhookController::class, 'show']
        );

    Route::post(
        '/webhooks/endpoint',
        [MerchantWebhookController::class, 'store']
        );

    Route::get(
        '/webhooks/logs',
        [MerchantWebhookController::class, 'logs']
        );

    Route::post(
        '/webhooks/test',
        [MerchantWebhookController::class, 'test']
        );

    Route::post(
        '/webhooks/logs/{id}/retry',
        [MerchantWebhookController::class, 'retry']
    )->whereNumber('id');

});
