<?php
namespace App\Http\Controllers\Api\Admin;
use App\Http\Controllers\Controller;
use App\Models\CustomerKYCVerification;
use App\Services\AuditService;
use App\Jobs\ProcessKYCVerificationJob;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class AdminKycController extends Controller
{
    public function index(Request $request) {
        $v=$request->validate(['search'=>['nullable','string','max:255'],'status'=>['nullable','in:pending,processing,verified,failed'],'per_page'=>['nullable','integer','between:5,100']]);
        $q=CustomerKYCVerification::query()->with('customer.business')->latest('id');
        if (! empty($v['status'])) $q->where('status',$v['status']);
        if (! empty($v['search'])) $q->where('reference','like','%'.$v['search'].'%');
        $p=$q->paginate($v['per_page']??20);
        $p->setCollection($p->getCollection()->map(fn($r)=>[
            'id'=>$r->id,'reference'=>$r->reference,'type'=>$r->type,'status'=>$r->status,'attempts'=>$r->attempts,
            'customer_name'=>$r->customer?->fullName(),'business_name'=>$r->customer?->business?->name,
            'masked_identity'=>$r->metadata['masked_identity']??null,'verified_at'=>$r->verified_at,
            'review_note'=>$r->metadata['review_note']??null,'created_at'=>$r->created_at,
        ]));
        return ApiResponse::paginated($p,\Illuminate\Http\Resources\Json\JsonResource::class);
    }
    public function review(Request $request, int $id) {
        $v=$request->validate(['note'=>['required','string','max:2000'],'retry'=>['required','boolean']]);
        return DB::transaction(function()use($request,$id,$v){
            $r=CustomerKYCVerification::query()->lockForUpdate()->findOrFail($id);
            $metadata=array_merge($r->metadata??[],['review_note'=>$v['note'],'reviewed_by_admin_id'=>$request->user()->id,'reviewed_at'=>now()->toIso8601String()]);
            if ($v['retry']) {
                if ($r->status!=='failed' || ! $r->identity_number) throw ValidationException::withMessages(['retry'=>['Only failed requests with retained identity data can be retried.']]);
                $r->fill(['status'=>'pending','attempts'=>0,'next_retry_at'=>now(),'processing_at'=>null,'failed_at'=>null,'last_error'=>null]);
                $r->customer->update(['kyc_status'=>'pending']);
            }
            $r->metadata=$metadata;$r->save();
            app(AuditService::class)->log($request->user()->id,'kyc.reviewed',$r,null,['note'=>$v['note'],'retry'=>$v['retry']]);
            if($v['retry']) ProcessKYCVerificationJob::dispatch($r->id)->afterCommit();
            return ApiResponse::success(['id'=>$r->id,'status'=>$r->status],$v['retry']?'Provider verification retry queued.':'Review note saved.');
        },3);
    }
}
