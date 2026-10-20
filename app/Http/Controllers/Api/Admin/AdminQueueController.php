<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminQueueController extends Controller
{
    public function failed(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'queue' => ['nullable', 'string', 'max:255'],
            'connection' => ['nullable', 'string', 'max:255'],
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        $query = DB::table(config('queue.failed.table', 'failed_jobs'))->latest('id');

        foreach (['queue', 'connection'] as $field) {
            if (! empty($validated[$field])) {
                $query->where($field, $validated[$field]);
            }
        }

        if (! empty($validated['search'])) {
            $search = trim($validated['search']);
            $query->where(function ($builder) use ($search): void {
                $builder->where('uuid', 'like', "%{$search}%")
                    ->orWhere('exception', 'like', "%{$search}%")
                    ->orWhere('payload', 'like', "%{$search}%");
            });
        }

        return response()->json([
            'success' => true,
            'data' => $query->paginate((int) ($validated['per_page'] ?? 20)),
        ]);
    }

    public function retry(Request $request, string $uuid): JsonResponse
    {
        $request->validate(['force' => ['nullable', 'boolean']]);

        $exists = DB::table(config('queue.failed.table', 'failed_jobs'))
            ->where('uuid', $uuid)
            ->exists();

        if (! $exists) {
            return response()->json(['success' => false, 'message' => 'Failed job was not found.'], 404);
        }

        $exitCode = Artisan::call('queue:retry', ['id' => [$uuid]]);

        return response()->json([
            'success' => $exitCode === 0,
            'message' => $exitCode === 0 ? 'Failed job queued for retry.' : 'Failed job could not be retried.',
        ], $exitCode === 0 ? 202 : 500);
    }

    public function forget(string $uuid): JsonResponse
    {
        $deleted = DB::table(config('queue.failed.table', 'failed_jobs'))
            ->where('uuid', $uuid)
            ->delete();

        return response()->json([
            'success' => $deleted > 0,
            'message' => $deleted > 0 ? 'Failed job removed.' : 'Failed job was not found.',
        ], $deleted > 0 ? 200 : 404);
    }
}
