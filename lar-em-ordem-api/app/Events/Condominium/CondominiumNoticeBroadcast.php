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
 * Aviso do condomínio em tempo real: publicado no canal privado de cada casa do prédio
 * (properties.{id}), quando o administrador o publica ou o edita.
 * Vai pela fila: se o Reverb estiver em baixo, publicar o aviso não falha.
 */
class CondominiumNoticeBroadcast implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public CondominiumNotice $notice,
        public bool $updated = false,
    ) {}

    /**
     * Um canal por casa do condomínio.
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

    /** Nome do evento no frontend: .listen('.condominium.notice', …) */
    public function broadcastAs(): string
    {
        return 'condominium.notice';
    }

    /**
     * Os mesmos campos do "sino" (GET /properties/{id}/condominium-notices), mais "updated".
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