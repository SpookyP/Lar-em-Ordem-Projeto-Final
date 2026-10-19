<?php

namespace App\Http\Controllers\ServiceProvider;

use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceProvider\IndexAssistanceRequestRequest;
use App\Http\Requests\ServiceProvider\StoreAssistanceRequestRequest;
use App\Http\Requests\ServiceProvider\UpdateAssistanceRequestRequest;
use App\Http\Resources\ServiceProvider\AssistanceRequestResource;
use App\Models\ServiceProvider\AssistanceRequest;
use App\Services\ServiceProvider\AssistanceRequestService;
use App\Services\ServiceProvider\ServiceProviderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AssistanceRequestController extends Controller
{
    public function __construct(
        private AssistanceRequestService $service,
        private ServiceProviderService $serviceProviderService,
    ) {}

    /**
     * Lista os pedidos de assistência do utilizador autenticado.
     * Permite filtrar através do parâmetro 'status' (ex: ?status=open).
     *
     * @param IndexAssistanceRequestRequest $request O pedido HTTP com o filtro validado.
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection Coleção de pedidos.
     */
    public function index(IndexAssistanceRequestRequest $request)
    {
        return AssistanceRequestResource::collection(
            $this->service->listRequestsByUser($request->user(), $request->validated('status'))
        );
    }

    /**
     * Abre um novo pedido de assistência para uma casa do residente autenticado.
     *
     * @param StoreAssistanceRequestRequest $request Dados validados.
     * @return \Illuminate\Http\JsonResponse Devolve o recurso criado com o status 201.
     */
    public function store(StoreAssistanceRequestRequest $request)
    {
        Gate::authorize('create', AssistanceRequest::class);

        $assistanceRequest = $this->service->create($request->validated(), $request->user());

        return (new AssistanceRequestResource($assistanceRequest))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Devolve os detalhes de um pedido de assistência, incluindo a casa e a categoria.
     *
     * @param AssistanceRequest $assistanceRequest Instância do pedido.
     * @return AssistanceRequestResource
     */
    public function show(AssistanceRequest $assistanceRequest)
    {
        Gate::authorize('view', $assistanceRequest);

        return new AssistanceRequestResource($this->service->loadFull($assistanceRequest));
    }

    /**
     * Atualiza um pedido de assistência enquanto estiver aberto.
     *
     * @param UpdateAssistanceRequestRequest $request Dados validados.
     * @param AssistanceRequest $assistanceRequest Instância do pedido.
     * @return AssistanceRequestResource Devolve o pedido atualizado.
     */
    public function update(UpdateAssistanceRequestRequest $request, AssistanceRequest $assistanceRequest)
    {
        Gate::authorize('update', $assistanceRequest);

        return new AssistanceRequestResource(
            $this->service->update($assistanceRequest, $request->validated())
        );
    }

    /**
     * Cancela um pedido de assistência aberto.
     *
     * @param AssistanceRequest $assistanceRequest Instância do pedido.
     * @return AssistanceRequestResource Devolve o pedido cancelado.
     */
    public function cancel(AssistanceRequest $assistanceRequest)
    {
        Gate::authorize('cancel', $assistanceRequest);

        return new AssistanceRequestResource($this->service->cancel($assistanceRequest));
    }

    /**
     * Lista os pedidos abertos disponíveis para o prestador autenticado
     * (da sua categoria e da sua zona).
     *
     * @param Request $request O pedido HTTP.
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection Coleção de pedidos.
     */
    public function available(Request $request)
    {
        Gate::authorize('viewAvailable', AssistanceRequest::class);

        $provider = $this->serviceProviderService->getByUser($request->user());

        return AssistanceRequestResource::collection(
            $this->service->listAvailableFor($provider)
        );
    }
}