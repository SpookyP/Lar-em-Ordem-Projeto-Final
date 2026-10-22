<?php

namespace App\Services\ServiceProvider;

use App\Models\Property\Property;
use App\Models\ServiceProvider\AssistanceRequest;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use App\Models\User\ServiceProvider as ServiceProviderModel;

class AssistanceRequestService
{
    /**
     * Devolve os pedidos de um utilizador, do mais recente para o mais antigo.
     *
     * @param User $user Dono dos pedidos.
     * @param string|null $status (Opcional) Filtro por estado.
     * @return Collection
     */
    public function listRequestsByUser(User $user, ?string $status = null): Collection
    {
        return AssistanceRequest::where('user_id', $user->id)
            ->when($status, fn ($q) => $q->where('status', $status))
            ->with(['property.address', 'problemCategory'])
            ->orderByDesc('created_at')
            ->get();
    }
    
    /**
     * Devolve os pedidos disponíveis para um prestador, do mais recente para o mais antigo.
     *
     * @param ServiceProviderModel $provider Prestador para quem se procuram pedidos.
     * @return Collection
     */
    public function listAvailableFor(ServiceProviderModel $provider): Collection
    {
        return AssistanceRequest::availableFor($provider)
            ->with(['property.address', 'problemCategory'])
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Abre um pedido de assistência para uma casa do utilizador.
     * A casa tem de ter um contrato ativo do utilizador.
     *
     * @param array $data Dados validados para a criação do pedido.
     * @param User $owner Residente que abre o pedido.
     * @return AssistanceRequest
     */
    public function create(array $data, User $owner): AssistanceRequest
    {
        $property = Property::findOrFail($data['property_id']);

        // reutiliza PropertyPolicy::view → contrato ativo
        if (Gate::forUser($owner)->denies('view', $property)) {
            throw ValidationException::withMessages([
                'property_id' => 'Só pode abrir pedidos para casas onde tem um contrato ativo.',
            ]);
        }

        $assistanceRequest = AssistanceRequest::create($data + [
            'user_id' => $owner->id,
        ]);

        return $this->loadFull($assistanceRequest);
    }

    /**
     * Atualiza um pedido. Só é possível enquanto estiver aberto.
     *
     * @param AssistanceRequest $assistanceRequest Instância do pedido a ser atualizado.
     * @param array $data Dados validados para a atualização.
     * @return AssistanceRequest
     */
    public function update(AssistanceRequest $assistanceRequest, array $data): AssistanceRequest
    {
        $this->ensureOpen($assistanceRequest, 'Só é possível editar pedidos abertos.');

        $assistanceRequest->update($data);

        return $this->loadFull($assistanceRequest);
    }

    /**
     * Cancela um pedido aberto e rejeita as propostas que ainda estavam pendentes.
     * Transação.
     *
     * @param AssistanceRequest $assistanceRequest Instância do pedido a cancelar.
     * @return AssistanceRequest
     */
    public function cancel(AssistanceRequest $assistanceRequest): AssistanceRequest
    {
        $this->ensureOpen($assistanceRequest, 'Só é possível cancelar pedidos abertos.');

        DB::transaction(function () use ($assistanceRequest) {
            $assistanceRequest->proposals()
                ->where('status', 'pending')
                ->update(['status' => 'rejected', 'response_date' => now()]);

            $assistanceRequest->update([
                'status'    => 'cancelled',
                'closed_at' => now(),
            ]);
        });

        return $this->loadFull($assistanceRequest);
    }

     /**
     * Carrega a casa (com morada), a categoria e a proposta aceite (com o prestador) de um pedido.
     *
     * @param AssistanceRequest $assistanceRequest Instância do pedido.
     * @return AssistanceRequest A mesma instância, com as relações carregadas.
     */
    public function loadFull(AssistanceRequest $assistanceRequest): AssistanceRequest
    {
        return $assistanceRequest->load([
            'property.address',
            'problemCategory',
            'acceptedProposal.serviceProvider',
            'attachments.file',
        ]);
    }

    /**
     * Garante que o pedido está aberto; caso contrário lança 422 com a mensagem indicada.
     *
     * @param AssistanceRequest $assistanceRequest Instância do pedido.
     * @param string $message Mensagem de erro a devolver.
     * @return void
     */
    private function ensureOpen(AssistanceRequest $assistanceRequest, string $message): void
    {
        if (! $assistanceRequest->isOpen()) {
            throw ValidationException::withMessages([
                'status' => $message,
            ]);
        }
    }
}