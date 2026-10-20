<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\VirtualAccount;
use App\Services\VirtualAccountLifecycleService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class AdminVirtualAccountController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected VirtualAccountLifecycleService $lifecycle) {}

    public function index(Request $request): JsonResponse
    {
        $v = $request->validate(['search' => ['nullable','string','max:255'],
            'provider_id' => ['nullable','integer','min:1'], 'status' => ['nullable','in:active,inactive,suspended,closed,expired'],
            'per_page' => ['nullable','integer','between:5,100']]);
        $q = VirtualAccount::query()->with(['customer','provider','business'])->latest('id');
        if (! empty($v['search'])) $q->where(fn ($b) => $b->where('account_number','like','%'.$v['search'].'%')->orWhere('account_name','like','%'.$v['search'].'%'));
        if (! empty($v['provider_id'])) $q->where('provider_id', $v['provider_id']);
        if (! empty($v['status'])) $q->where('status', $v['status']);
        return \App\Support\ApiResponse::paginated($q->paginate($v['per_page'] ?? 20), \App\Http\Resources\VirtualAccountResource::class);
    }

    public function regenerate(int $id, Request $request): JsonResponse
    {
        return $this->perform($id, $request, 'regenerate', 'Virtual account regenerated successfully.', 201);
    }

    public function activate(int $id, Request $request): JsonResponse
    {
        return $this->perform($id, $request, 'activate', 'Virtual account activated successfully.');
    }

    public function suspend(int $id, Request $request): JsonResponse
    {
        return $this->perform($id, $request, 'suspend', 'Virtual account suspended successfully.');
    }

    public function destroy(int $id, Request $request): JsonResponse
    {
        return $this->perform($id, $request, 'close', 'Virtual account closed successfully.');
    }

    private function perform(int $id, Request $request, string $method, string $message, int $status = 200): JsonResponse
    {
        try {
            $account = VirtualAccount::query()->with(['customer', 'business', 'provider'])->findOrFail($id);
            $updated = $this->lifecycle->{$method}($account, $request->user()?->id);
            return $this->successResponse($updated, $message, $status);
        } catch (Throwable $exception) {
            report($exception);
            return $this->errorResponse($exception->getMessage(), 422);
        }
    }
}
