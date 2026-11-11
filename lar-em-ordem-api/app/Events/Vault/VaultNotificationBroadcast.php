<?php

namespace App\Events\Vault;

use App\Models\Vault\Notification;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Alerta do Cofre Digital em tempo real: publicado no canal privado da habitação
 * (properties.{property_id}) quando um documento entra nos escalões de validade ou expira.
 * Vai pela fila: se o Reverb estiver em baixo, guardar o documento não falha.
 */
class VaultNotificationBroadcast implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Notification $notification,
    ) {}

    /**
     * Canal privado da habitação associada à notificação.
     *
     * @return array<PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("properties.{$this->notification->property_id}"),
        ];
    }

    /**
     * Nome do evento no frontend: .listen('.vault.notification', ...)
     */
    public function broadcastAs(): string
    {
        return 'vault.notification';
    }

    /**
     * Os mesmos campos devolvidos pelo NotificationResource no endpoint GET.
     */
    public function broadcastWith(): array
    {
        return [
            'id'          => $this->notification->id,
            'property_id' => $this->notification->property_id,
            'document_id' => $this->notification->document_id,
            'type'        => $this->notification->type,
            'title'       => $this->notification->title,
            'message'     => $this->notification->message,
            'is_read'     => !is_null($this->notification->read_at),
            'read_at'     => $this->notification->read_at?->toIso8601String(),
            'alert_date'  => $this->notification->alert_date?->toIso8601String(),
            'created_at'  => $this->notification->created_at?->toIso8601String(),
        ];
    }
}
