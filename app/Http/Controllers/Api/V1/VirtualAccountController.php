<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Http\Resources\VirtualAccountResource;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Transaction;
use App\Models\VirtualAccount;
use App\Services\VirtualAccountService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class VirtualAccountController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        protected VirtualAccountService $virtualAccountService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $merchant = $request->activeMerchant();
        $business = $request->activeBusiness();

        if (! $merchant || ! $business) {
            return $this->errorResponse(
                'Please select an active business.',
                422
            );
        }

        $accounts = VirtualAccount::query()
            ->where('merchant_id', $merchant->id)
            ->where('business_id', $business->id)
            ->with([
                'customer',
                'business',
                'provider',
            ])
            ->latest('id')
            ->paginate(
                min(
                    max(
                        (int) $request->input(
                            'per_page',
                            20
                        ),
                        5
                    ),
                    100
                )
            );

        return $this->paginatedResponse($accounts, VirtualAccountResource::class);
    }

    public function store(Request $request): JsonResponse
    {
        $merchant = $request->activeMerchant();
        $business = $request->activeBusiness();

        if (! $merchant || ! $business) {
            return $this->errorResponse(
                'Please select an active business.',
                422
            );
        }

        $validated = $request->validate([
            'customer_id' => [
                'required',
                'integer',
                Rule::exists('customers', 'id')->where(
                    fn ($query) =>
                        $query
                            ->where(
                                'merchant_id',
                                $merchant->id
                            )
                            ->where(
                                'business_id',
                                $business->id
                            )
                ),
            ],
            'provider_id' => [
                'nullable',
                'integer',
                Rule::exists('providers', 'id')->where(
                    fn ($query) =>
                        $query
                            ->where(
                                'type',
                                'virtual_account'
                            )
                            ->where(
                                'is_active',
                                true
                            )
                ),
            ],
            'reference' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique(
                    'virtual_accounts',
                    'reference'
                ),
            ],
            'metadata' => [
                'nullable',
                'array',
            ],
        ]);

        $customer = Customer::query()
            ->where('merchant_id', $merchant->id)
            ->where('business_id', $business->id)
            ->findOrFail(
                $validated['customer_id']
            );

        try {
            $account = $this->virtualAccountService
                ->createForCustomer(
                    $customer,
                    array_merge(
                        $validated['metadata'] ?? [],
                        [
                            'source' =>
                                'manual_creation',
                        ]
                    ),
                    $request->user()?->id,
                    isset($validated['provider_id'])
                        ? (int) $validated['provider_id']
                        : null,
                    $validated['reference'] ?? null
                );

            return $this->successResponse(
                $account,
                $account->wasRecentlyCreated
                    ? 'Virtual account created successfully.'
                    : 'Customer already has an active virtual account.',
                $account->wasRecentlyCreated ? 201 : 200
            );
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return $this->errorResponse(
                $exception->getMessage(),
                422
            );
        }
    }

    public function show(
        int $id,
        Request $request
    ): JsonResponse {
        $account = $this->accountForActiveBusiness(
            $id,
            $request
        );

        return $this->successResponse(
            new VirtualAccountResource($account->load(['customer', 'provider']))
        );
    }

    public function deactivate(
        int $id,
        Request $request
    ): JsonResponse {
        $account = $this->accountForActiveBusiness(
            $id,
            $request
        );

        if (! $account->isActive()) {
            return $this->errorResponse(
                'Account is already inactive.',
                400
            );
        }

        $oldStatus = $account->status;

        $account->update([
            'status' =>
                VirtualAccount::STATUS_INACTIVE,
        ]);

        AuditLog::query()->create([
            'user_id' => $request->user()->id,
            'auditable_type' =>
                VirtualAccount::class,
            'auditable_id' => $account->id,
            'event' =>
                'virtual_account_deactivated',
            'old_values' => [
                'status' => $oldStatus,
            ],
            'new_values' => [
                'status' =>
                    VirtualAccount::STATUS_INACTIVE,
            ],
            'ip_address' => $request->ip(),
            'user_agent' =>
                $request->userAgent(),
        ]);

        return $this->successResponse(
            $account->fresh([
                'customer',
                'business',
                'provider',
            ]),
            'Virtual account deactivated successfully.'
        );
    }

    public function getTransactions(
        int $id,
        Request $request
    ): JsonResponse {
        $account = $this->accountForActiveBusiness(
            $id,
            $request
        );

        $transactions = Transaction::query()
            ->where(
                'virtual_account_id',
                $account->id
            )
            ->latest('id')
            ->paginate(
                min(
                    max(
                        (int) $request->input(
                            'per_page',
                            20
                        ),
                        5
                    ),
                    100
                )
            );

        return $this->successResponse(
            $transactions
        );
    }

    public function policy(
        Request $request
    ): JsonResponse {
        $business = $request->activeBusiness();

        if (! $business) {
            return $this->errorResponse(
                'Please select an active business.',
                422
            );
        }

        return $this->successResponse(
            $this->virtualAccountService
                ->resolvedPolicy($business)
        );
    }

    private function accountForActiveBusiness(
        int $id,
        Request $request
    ): VirtualAccount {
        $merchant = $request->activeMerchant();
        $business = $request->activeBusiness();

        abort_unless(
            $merchant && $business,
            422,
            'Please select an active business.'
        );

        return VirtualAccount::query()
            ->where('merchant_id', $merchant->id)
            ->where('business_id', $business->id)
            ->findOrFail($id);
    }
}