<?php

namespace App\Http\Controllers\Api\Merchant;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Services\ApiKey\ApiKeyService;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ApiKeyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $merchant = $request->activeMerchant();
        $business = $request->activeBusiness();

        return response()->json([
            'status' => true,
            'data' => $merchant->apiKeys()
                ->where('business_id', $business->id)
                ->select('id', 'business_id', 'public_key', 'environment', 'status', 'ip_whitelist', 'last_used_at', 'revoked_at', 'created_at')
                ->latest('id')
                ->get(),
        ]);
    }

    public function generate(Request $request, ApiKeyService $apiKeyService, AuditService $auditService): JsonResponse
    {
        $data = $request->validate([
            'environment' => ['required', Rule::in([ApiKey::ENVIRONMENT_TEST, ApiKey::ENVIRONMENT_LIVE])],
            'password' => ['required', 'string'],
            'ip_whitelist' => ['nullable', 'array', 'max:20'],
            'ip_whitelist.*' => ['required', 'ip'],
        ]);

        if (! Hash::check($data['password'], $request->user()->password)) {
            return response()->json(['status' => false, 'message' => 'Incorrect password.'], 403);
        }

        $merchant = $request->activeMerchant();
        $business = $request->activeBusiness();

        if ($data['environment'] === ApiKey::ENVIRONMENT_LIVE && (! $merchant->live_access || ! $business->isApproved())) {
            return response()->json([
                'status' => false,
                'message' => 'Live access requires an approved merchant and business.',
            ], 403);
        }

        $apiKey = $apiKeyService->rotate(
            $merchant->id,
            $business->id,
            $data['environment'],
            $data['ip_whitelist'] ?? []
        );

        $auditService->log(
            $request->user()->id,
            'api_key.rotated',
            $business,
            null,
            ['api_key_id' => $apiKey['id'], 'environment' => $apiKey['environment']]
        );

        app(\App\Services\NotificationService::class)->notifyUser($request->user()->id, 'api_key_rotated', 'API key rotated', 'Your '.$apiKey['environment'].' API key was rotated for '.$business->name.'. If this was unexpected, revoke the key and contact support.');
        return response()->json([
            'status' => true,
            'message' => 'API key generated successfully.',
            'data' => $apiKey,
        ], 201);
    }

    public function reveal(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['password' => ['required', 'string']]);

        if (! Hash::check($data['password'], $request->user()->password)) {
            return response()->json(['status' => false, 'message' => 'Incorrect password.'], 403);
        }

        $apiKey = $request->activeMerchant()->apiKeys()
            ->where('business_id', $request->activeBusiness()->id)
            ->whereKey($id)
            ->firstOrFail();

        return response()->json([
            'status' => true,
            'data' => [
                'id' => $apiKey->id,
                'business_id' => $apiKey->business_id,
                'public_key' => $apiKey->public_key,
                'environment' => $apiKey->environment,
                'status' => $apiKey->status,
                'ip_whitelist' => $apiKey->ip_whitelist,
            ],
        ]);
    }

    public function revoke(Request $request, int $id, ApiKeyService $apiKeyService, AuditService $auditService): JsonResponse
    {
        $data = $request->validate(['password' => ['required', 'string']]);

        if (! Hash::check($data['password'], $request->user()->password)) {
            return response()->json(['status' => false, 'message' => 'Incorrect password.'], 403);
        }

        $business = $request->activeBusiness();
        $apiKey = $request->activeMerchant()->apiKeys()
            ->where('business_id', $business->id)
            ->whereKey($id)
            ->firstOrFail();

        $apiKeyService->revoke($apiKey);
        $auditService->log($request->user()->id, 'api_key.revoked', $business, null, ['api_key_id' => $apiKey->id]);

        return response()->json(['status' => true, 'message' => 'API key revoked successfully.']);
    }
}
