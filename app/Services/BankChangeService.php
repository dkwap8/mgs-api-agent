<?php
namespace App\Services;
use App\Models\BankChangeChallenge;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
class BankChangeService
{
    public function send(User $user, int $businessId, string $bankCode, string $accountNumber): string
    {
        $code = (string) random_int(100000,999999);
        $challenge = DB::transaction(function () use ($user,$businessId,$bankCode,$accountNumber,$code) {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $latest=BankChangeChallenge::query()->where('user_id',$user->id)->latest('id')->first();
            if ($latest && $latest->created_at->gt(now()->subMinute())) throw ValidationException::withMessages(['otp'=>['Wait 60 seconds before requesting another bank confirmation code.']]);
            BankChangeChallenge::query()->where('user_id',$user->id)->where('business_id',$businessId)->whereNull('consumed_at')->update(['consumed_at'=>now()]);
            return BankChangeChallenge::create(['reference'=>(string)Str::uuid(),'user_id'=>$user->id,'business_id'=>$businessId,
                'bank_code'=>$bankCode,'account_number'=>$accountNumber,'otp_hash'=>Hash::make($code),'expires_at'=>now()->addMinutes(5)]);
        },3);
        try {
            Mail::raw('Your MGS API bank account confirmation code is '.$code.'. It expires in 5 minutes. Account ending '.substr($accountNumber,-4).'. New settlement accounts have a 24-hour withdrawal delay.', function ($mail) use ($user) {
                $mail->to($user->email)->subject('Confirm settlement bank account');
            });
        } catch (\Throwable $e) {
            $challenge->update(['consumed_at'=>now()]);
            report($e);
            throw ValidationException::withMessages(['otp'=>['The confirmation email could not be sent. Please try again later.']]);
        }
        return $challenge->reference;
    }

    public function consume(User $user, int $businessId, string $reference, string $code, string $bankCode, string $accountNumber): void
    {
        $accepted = DB::transaction(function () use ($user,$businessId,$reference,$code,$bankCode,$accountNumber) {
            $c=BankChangeChallenge::query()->where('reference',$reference)->where('user_id',$user->id)->where('business_id',$businessId)->lockForUpdate()->first();
            if (! $c || $c->consumed_at || $c->expires_at->lte(now()) || $c->attempts>=5 || $c->bank_code!==$bankCode || $c->account_number!==$accountNumber) return false;
            $c->increment('attempts');
            if (! Hash::check($code,$c->otp_hash)) return false;
            $c->update(['consumed_at'=>now()]);
            return true;
        },3);
        if (! $accepted) throw ValidationException::withMessages(['otp'=>['Bank confirmation code is invalid, expired or already used.']]);
    }
}
