<?php

namespace App\Services\ServiceProvider;

use App\Models\Property\Property;
use App\Models\ServiceProvider\AssistanceRequest;
use App\Models\User\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use App\Models\User\ServiceProvider as ServiceProviderModel;
use Illuminate\Support\Arr;

class AssistanceRequestService
{
    public function __construct(private RequestAttachmentService $attachmentService) {}

     /**
     * Devolve os pedidos de um utilizador numa casa, do mais recente para o mais antigo, paginados.
     *
     * @param User $user Dono dos pedidos.
     * @param string $propertyId Casa onde o morador está.
     * @param string|null $status (Opcional) Filtro por estado.
     * @param int $perPage Número de pedidos por página.
     * @return LengthAwarePaginator
     */
    public function listRequestsByUser(User $user, string $propertyId, ?string $status = null, int $perPage = 5): LengthAwarePaginator
    {
        return AssistanceRequest::where('resident_id', $user->resident?->id)
            ->where('property_id', $propertyId)
            ->when($status, fn ($q) => $q->where('status', $status))
            ->with(['property.address', 'problemCategory'])
            ->withCount(['proposals as pending_proposals_count' => fn ($q) => $q->where('status', 'pending')])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }
    
     /**
     * Devolve os pedidos disponíveis para um prestador, do mais recente para o mais antigo, paginados.
     *
     * @param ServiceProviderModel $provider Prestador para quem se procuram pedidos.
     * @param int $perPage Número de pedidos por página.
     * @return LengthAwarePaginator
     */
    public function listAvailableFor(ServiceProviderModel $provider, int $perPage = 5): LengthAwarePaginator
    {
        return AssistanceRequest::availableFor($provider)
            ->with(['property.address', 'problemCategory'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Abre um pedido de assistência para uma casa do utilizador, com imagens opcionais (máx. 2).
     * A casa tem de ter um contrato ativo do utilizador.
     * Transação: se o upload de uma imagem falhar, o pedido não é criado.
     *
     * @param array $data Dados validados para a criação do pedido (pode incluir 'images').
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

        $assistanceRequest = DB::transaction(function () use ($data, $owner) {
            $assistanceRequest = AssistanceRequest::create(Arr::except($data, 'images') + [
                'resident_id' => $owner->resident->id,
            ]);

            $this->attachmentService->sync($assistanceRequest, $data['images'] ?? []);

            return $assistanceRequest;
        });

        return $this->loadFull($assistanceRequest);
    }

    /**
     * Atualiza um pedido e as suas imagens (junta 'images', remove 'remove_attachment_ids').
     * Só é possível enquanto estiver aberto. Transação.
     *
     * @param AssistanceRequest $assistanceRequest Instância do pedido a ser atualizado.
     * @param array $data Dados validados para a atualização.
     * @return AssistanceRequest
     */
    public function update(AssistanceRequest $assistanceRequest, array $data): AssistanceRequest
    {
        $this->ensureOpen($assistanceRequest, 'Só é possível editar pedidos abertos.');

        DB::transaction(function () use ($assistanceRequest, $data) {
            $assistanceRequest->update(Arr::except($data, ['images', 'remove_attachment_ids']));

            $this->attachmentService->sync(
                $assistanceRequest,
                $data['images'] ?? [],
                $data['remove_attachment_ids'] ?? [],
            );
        });

        return $this->loadFull($assistanceRequest->refresh());
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
     * Carrega o pedido para o ecrã de detalhe. Se quem vê for o dono,
     * junta também as propostas recebidas (com o prestador), da mais recente para a mais antiga.
     *
     * @param AssistanceRequest $assistanceRequest Instância do pedido.
     * @param User $viewer Utilizador que está a ver o pedido.
     * @return AssistanceRequest
     */
    public function loadForShow(AssistanceRequest $assistanceRequest, User $viewer): AssistanceRequest
    {
        $this->loadFull($assistanceRequest);

        if (Gate::forUser($viewer)->allows('viewProposals', $assistanceRequest)) {
            $assistanceRequest->load([
                'proposals' => fn ($q) => $q->with('serviceProvider')->orderByDesc('created_at'),
            ]);
        }

        return $assistanceRequest;
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