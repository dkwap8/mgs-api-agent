<?php
namespace App\Http\Controllers\Api\Admin;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
class AdminAuditController extends Controller
{
    public function index(Request $request) {
        $v=$request->validate(['search'=>['nullable','string','max:255'],'per_page'=>['nullable','integer','between:5,100']]);
        $q=AuditLog::query()->select(['id','user_id','admin_id','event','auditable_type','auditable_id','ip_address','created_at'])->latest('id');
        if(!empty($v['search']))$q->where('event','like','%'.$v['search'].'%');
        return ApiResponse::paginated($q->paginate($v['per_page']??20),\Illuminate\Http\Resources\Json\JsonResource::class);
    }
}
