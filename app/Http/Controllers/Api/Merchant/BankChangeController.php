<?php
namespace App\Http\Controllers\Api\Merchant;
use App\Http\Controllers\Controller;
use App\Services\BankChangeService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
class BankChangeController extends Controller
{
    public function send(Request $request, BankChangeService $service) {
        $v=$request->validate(['bank_code'=>['required','digits:3'],'account_number'=>['required','digits:10'], 'password'=>['required','string']]);
        if (! Hash::check($v['password'],$request->user()->password)) throw ValidationException::withMessages(['password'=>['Incorrect password.']]);
        $ref=$service->send($request->user(),$request->activeBusiness()->id,$v['bank_code'],$v['account_number']);
        return ApiResponse::success(['challenge_reference'=>$ref,'expires_in'=>300], 'Bank confirmation code sent to your registered email.');
    }
}
