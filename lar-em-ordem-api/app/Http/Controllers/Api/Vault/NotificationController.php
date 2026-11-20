<?php

namespace App\Http\Controllers\Api\Vault;

use App\Http\Controllers\Controller;
use App\Http\Requests\PerPageRequest;
use App\Http\Resources\Vault\NotificationResource;
use App\Models\Property\Property;
use App\Models\Vault\Notification;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotificationController extends Controller
{
    use AuthorizesRequests;

    public function index(PerPageRequest $request, Property $property): AnonymousResourceCollection
    {
        $this->authorize('manageVault', $property);

        $perPage = (int) ($request->validated()['per_page'] ?? 5);

        $query = $property->notifications()
            ->with('document')
            ->latest('alert_date')
            ->orderByDesc('id');

        if ($request->boolean('unread')) {
            $query->whereNull('read_at');
        }

        return NotificationResource::collection($query->paginate($perPage)->withQueryString());
    }

    public function markAsRead(Notification $notification): NotificationResource
    {
        $this->authorize('manageVault', $notification->property);

        if (is_null($notification->read_at)) {
            $notification->update(['read_at' => now()]);
        }

        return new NotificationResource($notification);
    }

    public function markAsUnread(Notification $notification): JsonResponse
    {
        $this->authorize('manageVault', $notification->property);

        $notification->update([
            'read_at' => null,
        ]);

        return response()->json([
            'message' => 'Notificação marcada como não lida.',
        ]);
    }

    public function markAllAsRead(Property $property): JsonResponse
    {
        $this->authorize('manageVault', $property);

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
        $this->authorize('manageVault', $notification->property);

        $notification->delete();

        return response()->json(['message' => 'Notificação removida com sucesso.'], 200);
    }
}
