<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\WalletResource;
use App\Models\WalletTransaction;
use App\Services\WalletService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct(
        protected WalletService $walletService
    ) {
    }

    public function balance(Request $request): JsonResponse
    {
        $merchant = $request->activeMerchant();
        $business = $request->activeBusiness();

        $wallet = $this->walletService->createWallet(
            $merchant->id,
            $business->id
        );

        return ApiResponse::success(
            new WalletResource($wallet),
            'Wallet balance loaded.'
        );
    }

    public function transactions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $merchant = $request->activeMerchant();
        $business = $request->activeBusiness();

        $transactions = $this->walletService->getTransactions(
            $merchant->id,
            $business->id,
            (int) ($validated['limit'] ?? 50)
        );

        return ApiResponse::success($transactions);
    }

    public function transactionDetails(
        string $reference,
        Request $request
    ): JsonResponse {
        $merchant = $request->activeMerchant();
        $business = $request->activeBusiness();

        $wallet = $this->walletService->createWallet(
            $merchant->id,
            $business->id
        );

        $transaction = WalletTransaction::query()
            ->where('wallet_id', $wallet->id)
            ->where('reference', $reference)
            ->firstOrFail();

        return ApiResponse::success($transaction);
    }

    public function summary(Request $request): JsonResponse
    {
        $merchant = $request->activeMerchant();
        $business = $request->activeBusiness();
        $wallet = $this->walletService->createWallet(
            $merchant->id,
            $business->id
        );

        $todayCredits = WalletTransaction::query()
            ->where('wallet_id', $wallet->id)
            ->whereIn('type', ['credit', 'refund'])
            ->where('status', WalletTransaction::STATUS_COMPLETED)
            ->whereDate('created_at', today())
            ->sum('amount');

        $todayDebits = WalletTransaction::query()
            ->where('wallet_id', $wallet->id)
            ->whereIn('type', ['debit', 'fee'])
            ->where('status', WalletTransaction::STATUS_COMPLETED)
            ->whereDate('created_at', today())
            ->sum('amount');

        return ApiResponse::success([
            'business_id' => $business->id,
            'balance' => new WalletResource($wallet),
            'today' => [
                'credits' => (float) $todayCredits,
                'debits' => (float) $todayDebits,
                'net' => (float) $todayCredits - (float) $todayDebits,
            ],
        ]);
    }
}
