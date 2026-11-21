<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate(['per_page' => ['sometimes', 'integer', 'min:1', 'max:100'], 'unread_only' => ['sometimes', 'boolean']]);
        $query = $this->baseQuery($request)->when($request->boolean('unread_only'), fn (Builder $query) => $query->whereNull('read_at'));
        $unreadCount = (clone $this->baseQuery($request))->whereNull('read_at')->count();

        return response()->json(['success' => true, 'data' => $query->latest()->paginate($request->integer('per_page', 15)), 'meta' => ['unread_count' => $unreadCount]]);
    }

    public function show(Request $request, int $notification): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->baseQuery($request)->findOrFail($notification)]);
    }

    public function markRead(Request $request, int $notification): JsonResponse
    {
        $record = $this->baseQuery($request)->findOrFail($notification);
        if (! $record->read_at) {
            $record->update(['read_at' => now()]);
        }

        return response()->json(['success' => true, 'message' => 'Notification marked as read.', 'data' => $record->fresh()]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $count = $this->baseQuery($request)->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json(['success' => true, 'message' => 'All notifications marked as read.', 'data' => ['updated_count' => $count]]);
    }

    private function baseQuery(Request $request): Builder
    {
        return match ($request->attributes->get('account_type', 'admin')) {
            'inspector' => AdminNotification::query()->where('staff_id', $request->user()->id),
            'user' => AdminNotification::query()->where('customer_id', $request->user()->id),
            default => AdminNotification::query()
                ->where('admin_id', $request->attributes->get('admin_id', $request->user()->id))
                ->whereNull('staff_id')->whereNull('customer_id'),
        };
    }
}
