<?php

namespace App\Http\Controllers\Condominium;

use App\Http\Controllers\Controller;
use App\Http\Requests\Condominium\StoreCondominiumNoticeRequest;
use App\Http\Requests\Condominium\UpdateCondominiumNoticeRequest;
use App\Http\Resources\Condominium\CondominiumNoticeResource;
use App\Models\Condominium\Condominium;
use App\Models\Condominium\CondominiumNotice;
use App\Models\Property\Property;
use App\Services\Condominium\CondominiumNoticeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Http\Requests\PerPageRequest;

class CondominiumNoticeController extends Controller
{
    public function __construct(private CondominiumNoticeService $service) {}

    /**
     * Histórico de avisos do condomínio (inclui expirados). Só o administrador.
     *
     * @param PerPageRequest $request O pedido HTTP com o tamanho da página validado.
     * @param Condominium $condominium Condomínio.
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(PerPageRequest $request, Condominium $condominium)
    {
        Gate::authorize('viewAny', [CondominiumNotice::class, $condominium]);

        return CondominiumNoticeResource::collection(
            $this->service->listForAdmin($condominium, $request->integer('per_page', 5))
        );
    }

    /**
     * Publica um aviso e envia-o por email aos residentes. Só o administrador.
     *
     * @param StoreCondominiumNoticeRequest $request Dados validados.
     * @param Condominium $condominium Condomínio.
     * @return \Illuminate\Http\JsonResponse 201 (Created).
     */
    public function store(StoreCondominiumNoticeRequest $request, Condominium $condominium)
    {
        Gate::authorize('create', [CondominiumNotice::class, $condominium]);

        return (new CondominiumNoticeResource($this->service->create($condominium, $request->validated())))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Edita um aviso; se mudou, volta a ficar por ler e o email é reenviado. Só o administrador.
     *
     * @param UpdateCondominiumNoticeRequest $request Dados validados.
     * @param CondominiumNotice $condominiumNotice Aviso.
     * @return CondominiumNoticeResource
     */
    public function update(UpdateCondominiumNoticeRequest $request, CondominiumNotice $condominiumNotice)
    {
        Gate::authorize('update', $condominiumNotice);

        return new CondominiumNoticeResource($this->service->update($condominiumNotice, $request->validated()));
    }

    /**
     * Apaga um aviso. Só o administrador.
     *
     * @param CondominiumNotice $condominiumNotice Aviso.
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(CondominiumNotice $condominiumNotice)
    {
        Gate::authorize('delete', $condominiumNotice);

        $this->service->delete($condominiumNotice);

        return response()->json(['message' => 'Aviso removido.']);
    }

    /**
     * O residente dá o "visto" no aviso.
     *
     * @param Request $request Pedido com o utilizador autenticado.
     * @param CondominiumNotice $condominiumNotice Aviso.
     * @return CondominiumNoticeResource
     */
    public function markRead(Request $request, CondominiumNotice $condominiumNotice)
    {
        Gate::authorize('markRead', $condominiumNotice);

        return new CondominiumNoticeResource($this->service->markRead($condominiumNotice, $request->user()));
    }

    /**
     * Sino do residente: avisos em vigor do condomínio da casa ativa, sem os que eliminou, com "read".
     * Casa sem condomínio: lista vazia.
     *
     * @param Request $request Pedido com o utilizador autenticado.
     * @param Property $property Casa ativa do residente.
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Http\JsonResponse
     */
    public function bell(Request $request, Property $property)
    {
        // reutiliza PropertyPolicy::view → contrato ativo nesta casa
        Gate::authorize('view', $property);

        if (! $property->condominium) {
            return response()->json(['data' => []]);
        }

        return CondominiumNoticeResource::collection(
            $this->service->listForBell($property->condominium, $request->user())
        );
    }

    /**
     * "Marcar tudo como lido" no sino (condomínio da casa ativa).
     *
     * @param Request $request Pedido com o utilizador autenticado.
     * @param Property $property Casa ativa do residente.
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAllRead(Request $request, Property $property)
    {
        Gate::authorize('view', $property);

        $count = $property->condominium
            ? $this->service->markAllRead($property->condominium, $request->user())
            : 0;

        return response()->json([
            'message'       => 'Todos os avisos foram marcados como lidos.',
            'updated_count' => $count,
        ]);
    }

    /**
     * O residente elimina o aviso do seu sino (não apaga o aviso).
     *
     * @param Request $request Pedido com o utilizador autenticado.
     * @param CondominiumNotice $condominiumNotice Aviso.
     * @return \Illuminate\Http\JsonResponse
     */
    public function dismiss(Request $request, CondominiumNotice $condominiumNotice)
    {
        Gate::authorize('dismiss', $condominiumNotice);

        $this->service->dismiss($condominiumNotice, $request->user());

        return response()->json(['message' => 'Aviso removido das notificações.']);
    }

}