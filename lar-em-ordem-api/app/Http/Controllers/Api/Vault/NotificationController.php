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

        // Validar o per_page enviado pelo frontend (limite 1 a 15)
        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:15'],
        ]);

        $perPage = $validated['per_page'] ?? 15;

        $query = $property->notifications()->with('document')->latest('alert_date');

        if ($request->boolean('unread')) {
            $query->whereNull('read_at');
        }

        // Aplica o per_page dinâmico e preserva a query string na paginação
        return NotificationResource::collection($query->paginate($perPage)->withQueryString());
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

    public function markAsUnread(\App\Models\Vault\Notification $notification): \Illuminate\Http\JsonResponse
    {
        // Verifica autorização se tiveres
        $this->authorize('update', $notification->property);

        $notification->update([
            'read_at' => null
        ]);

        return response()->json([
            'message' => 'Notificação marcada como não lida.'
        ]);
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
