<?php

namespace App\Http\Controllers\Api\Merchant;

use App\Http\Controllers\Controller;
use App\Models\BusinessActivation;
use App\Models\Customer;
use App\Services\KYCService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MerchantActivationController extends Controller
{
    public function __construct(protected KYCService $kycService) {}
    public function show(Request $request)
    {
        $merchant = $request->activeMerchant();
        $business = $request->activeBusiness();

        if (! $merchant) {
            return response()->json([
                'success' => false,
                'message' => 'Merchant account not found.',
            ], 404);
        }

        if (! $business) {
            return response()->json([
                'success' => false,
                'message' => 'Please create or select a business first.',
            ], 404);
        }

        $activation = BusinessActivation::query()
            ->where('business_id', $business->id)
            ->latest('id')
            ->first();

        return response()->json([
            'success' => true,
            'data' => $activation,
            'business' => $business,
        ]);
    }

    public function submit(Request $request)
    {
        $merchant = $request->activeMerchant();
        $business = $request->activeBusiness();

        abort_if($business && ($business->isApproved() || $business->isSuspended()), 422, 'An approved or suspended business cannot resubmit activation documents. Contact support for reviewed changes.');

        if (! $merchant) {
            return response()->json([
                'success' => false,
                'message' => 'Merchant account not found.',
            ], 404);
        }

        if (! $business) {
            return response()->json([
                'success' => false,
                'message' => 'Please create or select a business first.',
            ], 404);
        }

        $validated = $request->validate([
            'business_type' => ['required', Rule::in(['starter', 'registered'])],

            'business_name' => ['required', 'string', 'max:255'],
            'first_name' => ['nullable', 'required_if:business_type,starter', 'string', 'max:100'],
            'last_name' => ['nullable', 'required_if:business_type,starter', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'business_email' => ['required', 'email', 'max:255'],
            'business_phone' => ['required', 'regex:/^\d{11}$/'],
            'website_url' => ['required', 'url:http,https', 'max:2048'],
            'business_category' => ['required', 'string', 'max:255'],
            'business_description' => ['required', 'string', 'min:50'],

            'country' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'street_address' => ['required', 'string', 'max:255'],
            'landmark' => ['nullable', 'string', 'max:255'],

            'nin' => ['nullable', 'required_without:bvn', 'digits:11'],
            'bvn' => ['nullable', 'required_without:nin', 'digits:11'],

            'bank_name' => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'digits:10'],
            'account_name' => ['required', 'string', 'max:255'],

            'accept_terms' => ['accepted'],

            'proof_of_address' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],

            'cac_number' => ['nullable', 'required_if:business_type,registered', 'string', 'max:100'],
            'cac_certificate' => ['nullable', 'required_if:business_type,registered', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
            'cac_status_report' => ['nullable', 'required_if:business_type,registered', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
        ], [
            'first_name.required_if' => 'First name is required for a starter business.',
            'last_name.required_if' => 'Last name / surname is required for a starter business.',
            'website_url.required' => 'Please enter your website URL.',
            'website_url.url' => 'Please enter a valid website URL, including http:// or https://.',
            'business_phone.required' => 'Please enter your business phone number.',
            'business_phone.regex' => 'Your phone number must contain exactly 11 digits.',
            'business_description.min' => 'Business description must contain at least 50 characters.',
            'proof_of_address.required' => 'Proof of address is required.',
            'cac_number.required_if' => 'CAC / RC number is required for a registered business.',
            'cac_certificate.required_if' => 'CAC certificate is required for a registered business.',
            'cac_status_report.required_if' => 'CAC status report is required for a registered business.',
        ]);

        $existing = BusinessActivation::query()
            ->where('business_id', $business->id)
            ->whereIn('status', ['pending', 'under_review'])
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'You already have a pending activation request.',
            ], 422);
        }

        $proofOfAddress = $request->file('proof_of_address')
            ->store('activations/proof-address', 'local');

        $cacCertificate = null;
        $cacStatusReport = null;

        if ($request->hasFile('cac_certificate')) {
            $cacCertificate = $request->file('cac_certificate')
                ->store('activations/cac', 'local');
        }

        if ($request->hasFile('cac_status_report')) {
            $cacStatusReport = $request->file('cac_status_report')
                ->store('activations/cac', 'local');
        }

        $activation = DB::transaction(function () use (
            $request, $merchant, $business, $validated, $proofOfAddress, $cacCertificate, $cacStatusReport
        ): BusinessActivation {
            $activation = BusinessActivation::create([
                'merchant_id' => $merchant->id,
                'business_id' => $business->id,
                'user_id' => $request->user()->id,
                'business_type' => $validated['business_type'],
                'status' => 'under_review',
                'kyc_status' => 'pending',

                'business_name' => trim($validated['business_name']),
                'first_name' => $validated['business_type'] === 'starter' ? trim($validated['first_name']) : null,
                'last_name' => $validated['business_type'] === 'starter' ? trim($validated['last_name']) : null,
                'middle_name' => $validated['business_type'] === 'starter'
                    ? (($validated['middle_name'] ?? null) !== null ? trim($validated['middle_name']) : null)
                    : null,
                'business_email' => strtolower(trim($validated['business_email'])),
                'business_phone' => $validated['business_phone'],
                'website_url' => $validated['website_url'],
                'business_category' => trim($validated['business_category']),
                'business_description' => trim($validated['business_description']),

                'country' => trim($validated['country']),
                'state' => trim($validated['state']),
                'city' => trim($validated['city']),
                'street_address' => trim($validated['street_address']),
                'landmark' => isset($validated['landmark']) ? trim($validated['landmark']) : null,
                'proof_of_address' => $proofOfAddress,

                'nin' => $validated['nin'] ?? null,
                'nin_hash' => isset($validated['nin']) ? hash('sha256', $validated['nin']) : null,
                'bvn' => $validated['bvn'] ?? null,
                'bvn_hash' => isset($validated['bvn']) ? hash('sha256', $validated['bvn']) : null,

                'bank_name' => trim($validated['bank_name']),
                'account_number' => $validated['account_number'],
                'account_name' => trim($validated['account_name']),

                'cac_number' => $validated['business_type'] === 'registered' ? trim($validated['cac_number']) : null,
                'cac_certificate' => $validated['business_type'] === 'registered' ? $cacCertificate : null,
                'cac_status_report' => $validated['business_type'] === 'registered' ? $cacStatusReport : null,
                'accept_terms' => true,
            ]);

            $business->update([
                'name' => trim($validated['business_name']),
                'verification_status' => 'under_review',
                'is_verified' => false,
                'verified_at' => null,
                'activation_id' => $activation->id,
            ]);

            $ownerCustomer = Customer::query()->updateOrCreate(
                [
                    'merchant_id' => $merchant->id,
                    'business_id' => $business->id,
                    'external_reference' => 'OWNER-'.$business->id,
                ],
                [
                    'first_name' => $validated['first_name'] ?? $request->user()->name,
                    'last_name' => $validated['last_name'] ?? 'Owner',
                    'email' => strtolower(trim($validated['business_email'])),
                    'phone' => $validated['business_phone'],
                    'kyc_status' => Customer::KYC_PENDING,
                    'status' => Customer::STATUS_ACTIVE,
                    'metadata' => [
                        'source' => 'merchant_activation',
                        'business_owner' => true,
                    ],
                ]
            );

            $activation->update(['owner_customer_id' => $ownerCustomer->id]);

            $identityType = isset($validated['nin']) ? 'nin' : 'bvn';
            $identityNumber = $validated[$identityType];
            $submittedName = trim(($validated['first_name'] ?? '').' '.($validated['last_name'] ?? ''));

            $this->kycService->queueVerification(
                $ownerCustomer,
                $identityType,
                $identityNumber,
                $submittedName !== '' ? $submittedName : $request->user()->name
            );

            return $activation->fresh(['ownerCustomer', 'business']);
        }, 3);

        return response()->json([
            'success' => true,
            'message' => 'Business activation submitted successfully. Identity verification and virtual-account provisioning have been queued automatically.',
            'data' => $activation,
        ], 201);
    }
}
