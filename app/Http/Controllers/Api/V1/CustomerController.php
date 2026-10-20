<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Http\Resources\VirtualAccountResource;
use App\Models\Customer;
use App\Services\ActivityLogService;
use App\Services\KYCService;
use App\Services\NotificationService;
use App\Services\VirtualAccountService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        protected KYCService $kycService,
        protected VirtualAccountService $virtualAccountService,
        protected NotificationService $notificationService,
        protected ActivityLogService $activityLogService
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

        $customers = Customer::query()
            ->where('merchant_id', $merchant->id)
            ->where('business_id', $business->id)
            ->with([
                'kycIdentity',
                'virtualAccounts.provider',
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

        return $this->paginatedResponse($customers, CustomerResource::class);
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
            'first_name' => [
                'required',
                'string',
                'max:255',
            ],
            'last_name' => [
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('customers', 'email')
                    ->where(
                        fn ($query) =>
                            $query->where(
                                'business_id',
                                $business->id
                            )
                    ),
            ],
            'phone' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('customers', 'phone')
                    ->where(
                        fn ($query) =>
                            $query->where(
                                'business_id',
                                $business->id
                            )
                    ),
            ],
            'external_reference' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique(
                    'customers',
                    'external_reference'
                )->where(
                    fn ($query) =>
                        $query->where(
                            'business_id',
                            $business->id
                        )
                ),
            ],
            'metadata' => [
                'nullable',
                'array',
            ],
        ]);

        $customer = Customer::query()->create([
            'merchant_id' => $merchant->id,
            'business_id' => $business->id,
            'external_reference' =>
                $validated['external_reference']
                ?? 'CUST-' . Str::upper(
                    Str::random(12)
                ),
            'first_name' => trim(
                $validated['first_name']
            ),
            'last_name' => trim(
                $validated['last_name']
            ),
            'email' => isset($validated['email'])
                ? strtolower(
                    trim($validated['email'])
                )
                : null,
            'phone' => isset($validated['phone'])
                ? trim($validated['phone'])
                : null,
            'kyc_status' => Customer::KYC_PENDING,
            'status' => Customer::STATUS_ACTIVE,
            'metadata' => $validated['metadata'] ?? [],
        ]);

        $virtualAccount = $this->virtualAccountService
            ->tryAutoGenerate(
                $customer,
                [
                    'source' =>
                        'customer_registration',
                ],
                $request->user()?->id
            );

        $this->notificationService->customerRegistered(
            $customer->id,
            $merchant->id,
            $business->id,
            $customer->fullName(),
            $business->name
        );

        $this->activityLogService->customerRegistered(
            $customer->id,
            $merchant->id,
            $business->id,
            $customer->fullName(),
            $business->name,
            $request->user()?->id,
            $request
        );

        return $this->successResponse([
            'customer' => new CustomerResource($customer->fresh()->loadCount('virtualAccounts')),
            'virtual_account' => $virtualAccount,
        ], $virtualAccount['created'] ?? false
            ? 'Customer created and virtual account generated successfully.'
            : 'Customer created successfully.',
            201
        );
    }

    public function show(
        int $id,
        Request $request
    ): JsonResponse {
        $customer = $this->customerForActiveBusiness(
            $id,
            $request
        );

        return $this->successResponse(
            new CustomerResource($customer->loadCount('virtualAccounts'))
        );
    }

    public function update(
        int $id,
        Request $request
    ): JsonResponse {
        $customer = $this->customerForActiveBusiness(
            $id,
            $request
        );

        $validated = $request->validate([
            'first_name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],
            'last_name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'sometimes',
                'nullable',
                'email',
                'max:255',
                Rule::unique('customers', 'email')
                    ->where(
                        fn ($query) =>
                            $query->where(
                                'business_id',
                                $customer->business_id
                            )
                    )
                    ->ignore($customer->id),
            ],
            'phone' => [
                'sometimes',
                'nullable',
                'string',
                'max:30',
                Rule::unique('customers', 'phone')
                    ->where(
                        fn ($query) =>
                            $query->where(
                                'business_id',
                                $customer->business_id
                            )
                    )
                    ->ignore($customer->id),
            ],
            'metadata' => [
                'sometimes',
                'array',
            ],
        ]);

        $customer->update($validated);

        $this->activityLogService->customerUpdated(
            $customer->id,
            $customer->merchant_id,
            $customer->business_id,
            $customer->fullName(),
            $validated,
            $request->user()?->id,
            $request
        );

        return $this->successResponse(
            $customer->fresh([
                'business',
                'kycIdentity',
                'virtualAccounts.provider',
            ]),
            'Customer updated successfully.'
        );
    }

    public function verify(Request $request): JsonResponse
    {
        $v = $request->validate(['customer_id'=>['required','integer','min:1'], 'type'=>['required','in:bvn,nin'],
            'bvn'=>['required_if:type,bvn','nullable','digits:11'], 'nin'=>['required_if:type,nin','nullable','digits:11']]);
        return $v['type'] === 'bvn' ? $this->verifyBVN($v['customer_id'], $request) : $this->verifyNIN($v['customer_id'], $request);
    }

    public function verifyBVN(
        int $id,
        Request $request
    ): JsonResponse {
        $validated = $request->validate([
            'bvn' => [
                'required',
                'digits:11',
            ],
            'name' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        $customer = $this->customerForActiveBusiness(
            $id,
            $request
        );

        $result = $this->kycService->verifyBVN(
            $customer->id,
            $validated['bvn'],
            $validated['name'] ?? null
        );

        if (($result['status'] ?? null) === 'verified') {
            return $this->successResponse($result, 'BVN already verified.');
        }

        if (($result['status'] ?? null) === 'pending') {
            return $this->successResponse($result, 'BVN verification queued.', 202);
        }

        return $this->errorResponse($result['message'] ?? 'BVN verification failed.', 400);
    }

    public function verifyNIN(
        int $id,
        Request $request
    ): JsonResponse {
        $validated = $request->validate([
            'nin' => [
                'required',
                'digits:11',
            ],
            'name' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        $customer = $this->customerForActiveBusiness(
            $id,
            $request
        );

        $result = $this->kycService->verifyNIN(
            $customer->id,
            $validated['nin'],
            $validated['name'] ?? null
        );

        if (($result['status'] ?? null) === 'verified') {
            return $this->successResponse($result, 'NIN already verified.');
        }

        if (($result['status'] ?? null) === 'pending') {
            return $this->successResponse($result, 'NIN verification queued.', 202);
        }

        return $this->errorResponse($result['message'] ?? 'NIN verification failed.', 400);
    }

    public function kycStatus(
        int $id,
        Request $request
    ): JsonResponse {
        $customer = $this->customerForActiveBusiness(
            $id,
            $request
        );

        return $this->successResponse(
            $this->kycService
                ->getCustomerKYCStatus(
                    $customer->id
                )
        );
    }

    private function customerForActiveBusiness(
        int $id,
        Request $request
    ): Customer {
        $merchant = $request->activeMerchant();
        $business = $request->activeBusiness();

        abort_unless(
            $merchant && $business,
            422,
            'Please select an active business.'
        );

        return Customer::query()
            ->where('merchant_id', $merchant->id)
            ->where('business_id', $business->id)
            ->findOrFail($id);
    }
}