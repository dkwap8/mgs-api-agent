<?php
namespace App\Http\Controllers\Api\Admin;
use App\Http\Controllers\Controller;
use App\Models\PricingRule;
use App\Models\SystemSetting;
use App\Services\AuditService;
use App\Support\ApiResponse;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class AdminPricingController extends Controller
{
    public function index() { return ApiResponse::success(PricingRule::query()->orderBy('type')->orderBy('min_amount')->get()); }
    public function store(Request $request) { return $this->save($request, new PricingRule); }
    public function update(Request $request, PricingRule $pricingRule) { return $this->save($request, $pricingRule); }
    private function save(Request $request, PricingRule $rule) {
        $v = $request->validate(['name'=>['required','string','max:255'], 'type'=>['required','in:collection'],
            'min_amount'=>['nullable',new \App\Rules\ExactAmount,'numeric','min:0'],
            'max_amount'=>['nullable',new \App\Rules\ExactAmount,'numeric','min:0'],
            'fee_amount'=>['required',new \App\Rules\ExactAmount,'numeric','min:0'],
            'fee_percentage'=>['required',new \App\Rules\ExactAmount,'numeric','between:0,100'],
            'is_active'=>['required','boolean']]);
        if (($v['min_amount'] ?? null) !== null && ($v['max_amount'] ?? null) !== null && Money::decimal($v['max_amount'])->isLessThan($v['min_amount'])) throw ValidationException::withMessages(['max_amount'=>['Maximum must be at least the minimum.']]);
        if ($rule->exists && $rule->type !== 'collection') throw ValidationException::withMessages(['type'=>['Payout fees are configured through withdrawal settings.']]);
        return DB::transaction(function () use ($request,$rule,$v) {
            SystemSetting::query()->firstOrCreate(['key'=>'pricing_lock'], ['value'=>'1','group'=>'internal']);
            SystemSetting::query()->where('key','pricing_lock')->lockForUpdate()->firstOrFail();
            if ($v['is_active']) {
                $overlap = PricingRule::query()->where('type',$v['type'])->where('is_active',true);
                if ($rule->exists) $overlap->whereKeyNot($rule->id);
                if (($v['max_amount'] ?? null) !== null) $overlap->where(fn ($q)=>$q->whereNull('min_amount')->orWhere('min_amount','<=',Money::format($v['max_amount'])));
                if (($v['min_amount'] ?? null) !== null) $overlap->where(fn ($q)=>$q->whereNull('max_amount')->orWhere('max_amount','>=',Money::format($v['min_amount'])));
                if ($overlap->exists()) throw ValidationException::withMessages(['min_amount'=>['Active pricing ranges cannot overlap.']]);
            }
            $old=$rule->exists ? $rule->toArray() : null;
            $rule->fill($v)->save();
            app(AuditService::class)->log($request->user()->id,'pricing.updated',$rule,$old,$rule->toArray());
            return ApiResponse::success($rule);
        },3);
    }
}
