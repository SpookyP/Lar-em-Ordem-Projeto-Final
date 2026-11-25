<?php

namespace App\Events\Vault;

use App\Models\Vault\Notification;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Real-time Digital Vault alert, published on the property's private channel
 * (properties.{property_id}) when a document enters an expiry window or expires.
 * Queued, so saving the document still works when Reverb is down.
 */
class VaultNotificationBroadcast implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Notification $notification,
    ) {}

    /**
     * Private channel of the property the notification belongs to.
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
     * Event name on the frontend: .listen('.vault.notification', ...)
     */
    public function broadcastAs(): string
    {
        return 'vault.notification';
    }

    /**
     * Same fields that NotificationResource returns on the GET endpoint.
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
