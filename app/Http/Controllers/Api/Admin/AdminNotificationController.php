<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendNotificationJob;
use App\Models\Notification;
use App\Models\NotificationDelivery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminNotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:read,unread'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        $query = Notification::query()->latest();
        if (! empty($validated['type'])) $query->where('type', $validated['type']);
        if (($validated['status'] ?? null) === 'read') $query->where('is_read', true);
        if (($validated['status'] ?? null) === 'unread') $query->where('is_read', false);
        if (array_key_exists('user_id', $validated)) $query->where('user_id', $validated['user_id']);

        return response()->json(['success' => true, 'data' => $query->paginate($validated['per_page'] ?? 20)]);
    }

    public function unread(): JsonResponse
    {
        $query = Notification::query()->whereNull('user_id')->where('is_read', false);
        return response()->json(['success' => true, 'data' => ['count' => (clone $query)->count(), 'items' => $query->latest()->limit(10)->get()]]);
    }

    public function markRead(int $id): JsonResponse
    {
        $notification = Notification::query()->findOrFail($id);
        $notification->update(['is_read' => true]);
        return response()->json(['success' => true, 'message' => 'Notification marked as read.', 'data' => $notification]);
    }

    public function markAllRead(): JsonResponse
    {
        Notification::query()->whereNull('user_id')->where('is_read', false)->update(['is_read' => true]);
        return response()->json(['success' => true, 'message' => 'All administrator notifications marked as read.']);
    }

    public function destroy(int $id): JsonResponse
    {
        Notification::query()->findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Notification deleted successfully.']);
    }

    public function deliveries(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'in:pending,processing,delivered,failed,dead'],
            'channel' => ['nullable', 'string', 'max:32'],
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        $query = NotificationDelivery::query()->with(['notification:id,title,type', 'user:id,name,email'])->latest();
        if (! empty($validated['status'])) $query->where('status', $validated['status']);
        if (! empty($validated['channel'])) $query->where('channel', $validated['channel']);
        if (! empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(fn ($q) => $q->where('destination', 'like', "%{$search}%")->orWhere('subject', 'like', "%{$search}%")->orWhere('last_error', 'like', "%{$search}%"));
        }

        return response()->json([
            'success' => true,
            'data' => $query->paginate($validated['per_page'] ?? 20),
            'stats' => NotificationDelivery::query()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function retryDelivery(int $id): JsonResponse
    {
        $delivery = NotificationDelivery::query()->findOrFail($id);
        if ($delivery->status === NotificationDelivery::STATUS_DELIVERED) {
            return response()->json(['success' => false, 'message' => 'Delivered notifications cannot be retried.'], 422);
        }

        $delivery->forceFill(['status' => NotificationDelivery::STATUS_PENDING, 'attempts' => 0, 'next_retry_at' => null, 'failed_at' => null, 'last_error' => null])->save();
        SendNotificationJob::dispatch($delivery->id);

        return response()->json(['success' => true, 'message' => 'Notification delivery was queued for retry.'], 202);
    }

    public function forgetDelivery(int $id): JsonResponse
    {
        $delivery = NotificationDelivery::query()->findOrFail($id);
        if ($delivery->status === NotificationDelivery::STATUS_PROCESSING) {
            return response()->json(['success' => false, 'message' => 'A processing delivery cannot be removed.'], 422);
        }
        $delivery->delete();
        return response()->json(['success' => true, 'message' => 'Notification delivery removed.']);
    }
}
