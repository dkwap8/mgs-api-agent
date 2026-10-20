<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessActivation;
use Illuminate\Http\Request;

class AdminActivationController extends Controller
{
    public function index()
    {
        return response()->json([
            'status' => true,
            'data' => BusinessActivation::with(['merchant', 'user'])
                ->latest()
                ->paginate(20),
        ]);
    }

    public function show($id)
    {
        $activation = BusinessActivation::with(['merchant', 'user'])->findOrFail($id);

        return response()->json([
            'status' => true,
            'data' => $activation,
        ]);
    }

    public function approve(Request $request, $id)
    {
        $activation = BusinessActivation::with('merchant')->findOrFail($id);

        $activation->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $request->user()->id,
            'rejected_at' => null,
            'rejected_by' => null,
            'rejection_reason' => null,
        ]);

        $activation->merchant->update([
            'status' => 'active',
            'live_access' => true,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Business activation approved successfully.',
            'data' => $activation,
        ]);
    }

    public function reject(Request $request, $id)
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $activation = BusinessActivation::with('merchant')->findOrFail($id);

        $activation->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'rejected_by' => $request->user()->id,
            'rejection_reason' => $data['reason'],
        ]);

        $activation->merchant->update([
            'status' => 'rejected',
            'live_access' => false,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Business activation rejected.',
            'data' => $activation,
        ]);
    }
}