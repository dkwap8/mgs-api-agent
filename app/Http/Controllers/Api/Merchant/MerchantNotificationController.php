<?php
namespace App\Http\Controllers\Api\Merchant;
use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
class MerchantNotificationController extends Controller
{
    public function unread(Request $request) {
        $q=Notification::query()->where('user_id',$request->user()->id)->where('is_read',false);
        return ApiResponse::success(['count'=>(clone $q)->count(),'items'=>$q->latest('id')->limit(10)->get()]);
    }
    public function read(Request $request,int $id) {
        Notification::query()->where('user_id',$request->user()->id)->findOrFail($id)->update(['is_read'=>true]);
        return ApiResponse::success(null,'Notification marked read.');
    }
    public function readAll(Request $request) {
        Notification::query()->where('user_id',$request->user()->id)->where('is_read',false)->update(['is_read'=>true]);
        return ApiResponse::success(null,'Notifications marked read.');
    }
}
