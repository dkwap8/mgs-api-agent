<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\PlatformReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminReportController extends Controller
{
    public function __construct(private readonly PlatformReportService $reports)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period' => ['nullable', Rule::in(['today', 'yesterday', '7days', '30days', 'month', 'year', 'custom', 'all'])],
            'start_date' => ['nullable', 'date', 'required_if:period,custom'],
            'end_date' => ['nullable', 'date', 'required_if:period,custom', 'after_or_equal:start_date'],
        ]);

        $period = $validated['period'] ?? '30days';

        return response()->json([
            'success' => true,
            'data' => $this->reports->build(
                $period,
                $validated['start_date'] ?? null,
                $validated['end_date'] ?? null,
            ),
        ]);
    }
}
