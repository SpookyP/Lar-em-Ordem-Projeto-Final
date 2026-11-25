<?php

namespace App\Events\Condominium;

use App\Models\Condominium\CondominiumNotice;
use App\Models\Property\Property;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Real-time condominium notice, published on the private channel of each property in the building
 * (properties.{id}) when the admin publishes or edits it.
 * Queued, so publishing the notice still works when Reverb is down.
 */
class CondominiumNoticeBroadcast implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public CondominiumNotice $notice,
        public bool $updated = false,
    ) {}

    /**
     * One channel per property in the condominium.
     *
     * @return array<PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return Property::where('condominium_id', $this->notice->condominium_id)
            ->pluck('id')
            ->map(fn (string $propertyId) => new PrivateChannel("properties.{$propertyId}"))
            ->all();
    }

    /** Event name on the frontend: .listen('.condominium.notice', …) */
    public function broadcastAs(): string
    {
        return 'condominium.notice';
    }

    /**
     * Same fields as the bell (GET /properties/{id}/condominium-notices), plus "updated".
     */
    public function broadcastWith(): array
    {
        return [
            'id'           => $this->notice->id,
            'title'        => $this->notice->title,
            'message'      => $this->notice->message,
            'priority'     => $this->notice->priority,
            'published_at' => $this->notice->published_at?->toIso8601String(),
            'expires_at'   => $this->notice->expires_at?->toIso8601String(),
            'read'         => false,
            'updated'      => $this->updated,
        ];
    }
}