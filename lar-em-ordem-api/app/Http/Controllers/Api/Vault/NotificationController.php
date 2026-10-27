<?php

namespace App\Http\Controllers\Api\Vault;

use App\Http\Controllers\Controller;
use App\Http\Resources\Vault\NotificationResource;
use App\Models\Property\Property;
use App\Models\Vault\Notification;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request, Property $property)
    {
        $this->authorize('view', $property);

        $query = $property->notifications()->with('document')->latest('alert_date');

        if ($request->boolean('unread')) {
            $query->whereNull('read_at');
        }

        return NotificationResource::collection($query->paginate(15));
    }

    public function unreadCount(Property $property): JsonResponse
    {
        $this->authorize('view', $property);

        $count = $property->notifications()->whereNull('read_at')->count();

        return response()->json(['unread_count' => $count]);
    }

    public function markAsRead(Notification $notification): NotificationResource
    {
        $this->authorize('update', $notification->property);

        if (is_null($notification->read_at)) {
            $notification->update(['read_at' => now()]);
        }

        return new NotificationResource($notification);
    }

    public function markAllAsRead(Property $property): JsonResponse
    {
        $this->authorize('update', $property);

        $updated = $property->notifications()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'message' => 'Todas as notificações foram marcadas como lidas.',
            'updated_count' => $updated,
        ]);
    }

    public function destroy(Notification $notification): JsonResponse
    {
        $this->authorize('update', $notification->property);

        $notification->delete();

        return response()->json(['message' => 'Notificação removida com sucesso.'], 200);
    }
}
