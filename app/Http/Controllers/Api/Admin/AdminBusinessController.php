<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Services\BusinessLifecycleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminBusinessController extends Controller
{
    public function suspend(Request $request, Business $business, BusinessLifecycleService $service): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Business suspended successfully.',
            'data' => $service->suspend($business, $request->user(), $validated['reason']),
        ]);
    }

    public function reactivate(Request $request, Business $business, BusinessLifecycleService $service): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Business reactivated successfully.',
            'data' => $service->reactivate($business, $request->user()),
        ]);
    }
}
