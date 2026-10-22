<?php

namespace App\Http\Controllers\ServiceProvider;

use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceProvider\StoreProposalRequest;
use App\Http\Requests\ServiceProvider\UpdateProposalRequest;
use App\Http\Resources\ServiceProvider\ProposalResource;
use App\Models\ServiceProvider\AssistanceRequest;
use App\Models\ServiceProvider\Proposal;
use App\Services\ServiceProvider\ProposalService;
use App\Services\ServiceProvider\ServiceProviderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProposalController extends Controller
{
    public function __construct(
        private ProposalService $service,
        private ServiceProviderService $serviceProviderService,
    ) {}

    /**
     * Lista as propostas recebidas por um pedido. Só o dono do pedido as pode ver.
     *
     * @param AssistanceRequest $assistanceRequest Instância do pedido.
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection Coleção de propostas.
     */
    public function index(AssistanceRequest $assistanceRequest)
    {
        Gate::authorize('viewProposals', $assistanceRequest);

        return ProposalResource::collection($this->service->listByRequest($assistanceRequest));
    }

    /**
     * Lista as propostas enviadas pelo prestador autenticado.
     *
     * @param Request $request O pedido HTTP.
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection Coleção de propostas.
     */
    public function listProviderProposals(Request $request)
    {
        Gate::authorize('create', Proposal::class);

        $provider = $this->serviceProviderService->getByUser($request->user());

        return ProposalResource::collection($this->service->listByProvider($provider));
    }

    /**
     * Envia uma proposta a um pedido, em nome do prestador autenticado.
     *
     * @param StoreProposalRequest $request Dados validados.
     * @param AssistanceRequest $assistanceRequest Pedido a que responde.
     * @return \Illuminate\Http\JsonResponse Devolve o recurso criado com o status 201 (Created).
     */
    public function store(StoreProposalRequest $request, AssistanceRequest $assistanceRequest)
    {
        Gate::authorize('create', Proposal::class);

        $provider = $this->serviceProviderService->getByUser($request->user());
        $proposal = $this->service->create($assistanceRequest, $provider, $request->validated());

        return (new ProposalResource($proposal))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Edita uma proposta pendente. Só o prestador dono da proposta a pode editar.
     *
     * @param UpdateProposalRequest $request Dados validados.
     * @param Proposal $proposal Instância da proposta.
     * @return ProposalResource Devolve a proposta atualizada.
     */
    public function update(UpdateProposalRequest $request, Proposal $proposal)
    {
        Gate::authorize('update', $proposal);

        return new ProposalResource($this->service->update($proposal, $request->validated()));
    }

    /**
     * Retira uma proposta pendente. Só o prestador dono da proposta a pode retirar.
     *
     * @param Proposal $proposal Instância da proposta.
     * @return ProposalResource Devolve a proposta retirada.
     */
    public function withdrawByProvider(Proposal $proposal)
    {
        Gate::authorize('withdraw', $proposal);

        return new ProposalResource($this->service->withdraw($proposal));
    }

    /**
     * Aceita uma proposta: rejeita as restantes pendentes e passa o pedido a 'closed'.
     * Só o dono do pedido a pode aceitar.
     *
     * @param Proposal $proposal Instância da proposta.
     * @return ProposalResource Devolve a proposta aceite.
     */
    public function acceptByResident(Proposal $proposal)
    {
        Gate::authorize('accept', $proposal);

        return new ProposalResource($this->service->accept($proposal));
    }

    /**
    * Rejeita uma proposta pendente; o pedido continua aberto. Só o dono do pedido a pode rejeitar.
     *
     * @param Proposal $proposal Instância da proposta.
     * @return ProposalResource Devolve a proposta rejeitada.
     */
    public function rejectByResident(Proposal $proposal)
    {
        Gate::authorize('reject', $proposal);

        return new ProposalResource($this->service->reject($proposal));
    }
}