<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Services\TransactionService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TransactionController extends Controller
{
    public function __construct(private readonly TransactionService $transactions) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in([
                Transaction::STATUS_PENDING,
                Transaction::STATUS_PROCESSING,
                Transaction::STATUS_SUCCESS,
                Transaction::STATUS_FAILED,
                Transaction::STATUS_REVERSED,
            ])],
            'type' => ['nullable', Rule::in([
                Transaction::TYPE_COLLECTION,
                Transaction::TYPE_PAYOUT,
                Transaction::TYPE_REFUND,
            ])],
            'provider_id' => ['nullable', 'integer', 'min:1'],
            'customer_id' => ['nullable', 'integer', 'min:1'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        return ApiResponse::paginated(
            $this->transactions->paginateForBusiness(
                $request->activeMerchant(),
                $request->activeBusiness(),
                $validated
            ),
            TransactionResource::class
        );
    }

    public function show(string $reference, Request $request): JsonResponse
    {
        return ApiResponse::success(new TransactionResource(
            $this->transactions->findForBusiness(
                $request->activeMerchant(),
                $request->activeBusiness(),
                $reference
            )
        ));
    }

    public function summary(Request $request): JsonResponse
    {
        return ApiResponse::success(
            $this->transactions->summaryForBusiness(
                $request->activeMerchant(),
                $request->activeBusiness()
            )
        );
    }
}
