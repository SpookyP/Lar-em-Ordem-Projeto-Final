<?php

namespace App\Http\Controllers\Api\Vault;

use App\Http\Controllers\Controller;
use App\Models\Vault\Notification;
use App\Http\Resources\Vault\NotificationResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    /**
     * Lista as notificações de uma determinada habitação.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'property_id' => ['required', 'integer', 'exists:properties,id']
        ]);

        $notifications = Notification::where('property_id', $request->property_id)
            ->orderBy('alert_date', 'desc')
            ->get();

        return NotificationResource::collection($notifications);
    }

    /**
     * Marca uma notificação como lida.
     */
    public function markAsRead(Notification $notification): JsonResponse
    {
        if (is_null($notification->read_at)) {
            $notification->update(['read_at' => now()]);
        }

        return response()->json([
            'message' => 'Notification marked as read.',
            'data' => new NotificationResource($notification)
        ]);
    }
}
