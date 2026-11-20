<?php

namespace App\Http\Controllers\ServiceProvider;

use App\Http\Controllers\Controller;
use App\Http\Requests\PerPageRequest;
use App\Http\Requests\ServiceProvider\IndexAssistanceRequestRequest;
use App\Http\Requests\ServiceProvider\AvailableAssistanceRequestRequest;
use App\Http\Requests\ServiceProvider\StoreAssistanceRequestRequest;
use App\Http\Requests\ServiceProvider\UpdateAssistanceRequestRequest;
use App\Http\Resources\ServiceProvider\AssistanceRequestResource;
use App\Models\ServiceProvider\AssistanceRequest;
use App\Models\ServiceProvider\RequestAttachment;
use App\Services\Auth\ProfileService;
use App\Services\FileStorage\FileStorageService;
use App\Services\ServiceProvider\AssistanceRequestService;
use Illuminate\Support\Facades\Gate;

class AssistanceRequestController extends Controller
{
    public function __construct(
        private AssistanceRequestService $service,
        private ProfileService $profiles,
    ) {}

     /**
     * Lista os pedidos de assistência do utilizador autenticado numa casa (?property_id=, obrigatório), paginados.
     * Permite filtrar através do parâmetro 'status' (ex: ?status=open) e escolher o tamanho da página (?per_page=).
     *
     * @param IndexAssistanceRequestRequest $request O pedido HTTP com o filtro validado.
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection Coleção de pedidos.
     */
    public function index(IndexAssistanceRequestRequest $request)
    {
        return AssistanceRequestResource::collection(
            $this->service->listRequestsByUser(
                $request->user(),
                $request->validated('property_id'),
                $request->validated('status'),
                $request->integer('per_page', 5),
            )
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
     * Se for o dono a ver, inclui também as propostas recebidas, paginadas (?page=&per_page=),
     * com a paginação em proposals_meta.
     *
     * @param PerPageRequest $request O pedido HTTP com o tamanho da página validado.
     * @param AssistanceRequest $assistanceRequest Instância do pedido.
     * @return AssistanceRequestResource
     */
    public function show(PerPageRequest $request, AssistanceRequest $assistanceRequest)
    {
        Gate::authorize('view', $assistanceRequest);

        $proposals = $this->service->loadForShow($assistanceRequest, $request->user(), $request->integer('per_page', 5));
        $resource = new AssistanceRequestResource($assistanceRequest);

        return $proposals ? $resource->additional(['proposals_meta' => [
            'current_page' => $proposals->currentPage(),
            'last_page'    => $proposals->lastPage(),
            'per_page'     => $proposals->perPage(),
            'total'        => $proposals->total(),
        ]]) : $resource;
    }
    
    /**
     * Atualiza um pedido de assistência enquanto estiver aberto, incluindo as imagens.
     * Com imagens, enviar multipart com POST e '_method=PATCH'.
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
     * (das suas categorias e zonas), com pesquisa opcional por texto e localização.
     *
     * @param AvailableAssistanceRequestRequest $request O pedido HTTP com a pesquisa e o tamanho da página validados.
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection Coleção de pedidos (paginada).
     */
    public function available(AvailableAssistanceRequestRequest $request)
    {
        Gate::authorize('viewAvailable', AssistanceRequest::class);

        $provider = $this->profiles->get($request->user(), 'service_provider');

        return AssistanceRequestResource::collection(
            $this->service->listAvailableFor(
                $provider,
                $request->integer('per_page', 5),
                $request->validated('search'),
                $request->validated('location'),
            )
        );
    }
    
    /**
     * A photo or file attached to a request: whoever may see the request may see its attachments.
     */
    public function attachmentContent(RequestAttachment $requestAttachment, FileStorageService $files)
    {
        abort_if($requestAttachment->assistanceRequest === null, 404);

        Gate::authorize('view', $requestAttachment->assistanceRequest);

        return $files->contentResponse($requestAttachment->file);
    }
}