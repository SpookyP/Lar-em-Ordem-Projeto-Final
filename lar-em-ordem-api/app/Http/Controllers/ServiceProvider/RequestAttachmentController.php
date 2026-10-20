<?php

namespace App\Http\Controllers\ServiceProvider;

use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceProvider\StoreRequestAttachmentRequest;
use App\Http\Resources\ServiceProvider\RequestAttachmentResource;
use App\Models\ServiceProvider\AssistanceRequest;
use App\Models\ServiceProvider\RequestAttachment;
use App\Services\ServiceProvider\RequestAttachmentService;
use Illuminate\Support\Facades\Gate;

class RequestAttachmentController extends Controller
{
    public function __construct(private RequestAttachmentService $service) {}

    /**
     * Junta 1 ou 2 imagens a um pedido aberto. Só o dono do pedido.
     *
     * @param StoreRequestAttachmentRequest $request Imagens validadas ('images[]').
     * @param AssistanceRequest $assistanceRequest Instância do pedido.
     * @return \Illuminate\Http\JsonResponse Todas as imagens do pedido, com o status 201 (Created).
     */
    public function store(StoreRequestAttachmentRequest $request, AssistanceRequest $assistanceRequest)
    {
        Gate::authorize('update', $assistanceRequest);

        $attachments = $this->service->addImages($assistanceRequest, $request->user(), $request->file('images'));

        return RequestAttachmentResource::collection($attachments)
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Remove uma imagem de um pedido aberto. Só o dono do pedido.
     *
     * @param AssistanceRequest $assistanceRequest Instância do pedido.
     * @param RequestAttachment $attachment Imagem a remover (tem de pertencer ao pedido).
     * @return \Illuminate\Http\JsonResponse Confirmação da remoção.
     */
    public function destroy(AssistanceRequest $assistanceRequest, RequestAttachment $attachment)
    {
        Gate::authorize('update', $assistanceRequest);

        $this->service->delete($assistanceRequest, $attachment);

        return response()->json(['message' => 'Imagem removida.']);
    }
}