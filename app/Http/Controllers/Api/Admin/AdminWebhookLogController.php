<?php
namespace App\Http\Controllers\Api\Admin;
use App\Http\Controllers\Controller;
use App\Models\WebhookLog;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
class AdminWebhookLogController extends Controller
{
    public function index(Request $request) {
        $v=$request->validate(['search'=>['nullable','string','max:255'],'status'=>['nullable','in:pending,delivered,failed'],'per_page'=>['nullable','integer','between:5,100']]);
        $q=WebhookLog::query()->select(['id','merchant_id','business_id','event','status','status_code','attempt','next_retry_at','created_at'])->latest('id');
        if(!empty($v['search']))$q->where('event','like','%'.$v['search'].'%');
        if(!empty($v['status']))$q->where('status',$v['status']);
        return ApiResponse::paginated($q->paginate($v['per_page']??20),\Illuminate\Http\Resources\Json\JsonResource::class);
    }
}
