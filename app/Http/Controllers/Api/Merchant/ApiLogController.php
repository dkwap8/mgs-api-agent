<?php
namespace App\Http\Controllers\Api\Merchant;
use App\Http\Controllers\Controller;
use App\Models\ApiLog;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
class ApiLogController extends Controller
{
    public function index(Request $request): JsonResponse {
        $v = $request->validate(['endpoint' => ['nullable','string','max:255'],
            'status_code' => ['nullable','integer','between:100,599'], 'per_page' => ['nullable','integer','between:5,100']]);
        $q = ApiLog::query()->where('merchant_id', $request->activeMerchant()->id)
            ->where('business_id', $request->activeBusiness()->id)
            ->select(['id','endpoint','method','status_code','ip_address','duration','created_at'])->latest('id');
        if (! empty($v['endpoint'])) $q->where('endpoint', 'like', '%'.$v['endpoint'].'%');
        if (! empty($v['status_code'])) $q->where('status_code', $v['status_code']);
        return ApiResponse::paginated($q->paginate($v['per_page'] ?? 20), \Illuminate\Http\Resources\Json\JsonResource::class);
    }
}
