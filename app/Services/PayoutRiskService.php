<?php
namespace App\Services;
use App\Models\Device;
use App\Models\User;
use Illuminate\Http\Request;
class PayoutRiskService
{
    public function reason(User $user,Request $request): ?string {
        $fingerprint=app(AccountSecurityService::class)->deviceFingerprint($request);
        $device=Device::query()->where('user_id',$user->id)->where('device_id',$fingerprint)->whereNull('revoked_at')->first();
        if(! $device || ! $device->trusted)return 'New or untrusted device requires administrator review.';
        if($device->ip_address!==$request->ip())return 'Device IP changed; administrator review required.';
        return null;
    }
}
