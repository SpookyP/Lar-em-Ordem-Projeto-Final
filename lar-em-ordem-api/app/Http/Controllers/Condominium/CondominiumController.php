<?php

namespace App\Http\Controllers\Condominium;

use App\Http\Controllers\Controller;
use App\Http\Requests\Condominium\JoinCondominiumRequest;
use App\Http\Requests\Condominium\LeaveCondominiumRequest;
use App\Http\Requests\Condominium\StoreCondominiumRequest;
use App\Http\Requests\Condominium\UpdateCondominiumRequest;
use App\Http\Requests\ServiceProvider\PerPageRequest;
use App\Http\Resources\Condominium\CondominiumResource;
use App\Models\Condominium\Condominium;
use App\Services\Condominium\CondominiumAdminService;
use App\Services\Condominium\CondominiumService;
use Illuminate\Support\Facades\Gate;

class CondominiumController extends Controller
{
    public function __construct(
        private CondominiumService $service,
        private CondominiumAdminService $adminService,
    ) {}

    /**
     * Lista os condomínios que o utilizador administra ou onde tem uma casa.
     *
     * @param PerPageRequest $request O pedido HTTP com o tamanho da página validado.
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(PerPageRequest $request)
    {
        Gate::authorize('viewAny', Condominium::class);

        return CondominiumResource::collection(
            $this->service->listFor($request->user(), $request->integer('per_page', 5))
        );
    }

    /**
     * Cria um condomínio gerido pelo administrador autenticado.
     *
     * @param StoreCondominiumRequest $request Dados validados.
     * @return \Illuminate\Http\JsonResponse 201 (Created).
     */
    public function store(StoreCondominiumRequest $request)
    {
        Gate::authorize('create', Condominium::class);

        $admin = $this->adminService->getByUser($request->user());

        return (new CondominiumResource($this->service->create($request->validated(), $admin)))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Devolve um condomínio. O administrador recebe também as frações ligadas.
     *
     * @param Condominium $condominium Condomínio.
     * @return CondominiumResource
     */
    public function show(Condominium $condominium)
    {
        Gate::authorize('view', $condominium);

        return new CondominiumResource(
            $this->service->loadFull($condominium, withProperties: Gate::allows('update', $condominium))
        );
    }

    /**
     * Atualiza o condomínio (e/ou gera novo código de convite). Só o administrador.
     *
     * @param UpdateCondominiumRequest $request Dados validados.
     * @param Condominium $condominium Condomínio.
     * @return CondominiumResource
     */
    public function update(UpdateCondominiumRequest $request, Condominium $condominium)
    {
        Gate::authorize('update', $condominium);

        return new CondominiumResource($this->service->update($condominium, $request->validated()));
    }

    /**
     * Remove o condomínio e desliga as casas. Só o administrador.
     *
     * @param Condominium $condominium Condomínio.
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Condominium $condominium)
    {
        Gate::authorize('delete', $condominium);

        $this->service->delete($condominium);

        return response()->json(['message' => 'Condomínio removido.']);
    }

    /**
     * Liga uma casa do residente ao condomínio do código de convite.
     *
     * @param JoinCondominiumRequest $request Dados validados (invite_code, property_id).
     * @return CondominiumResource
     */
    public function join(JoinCondominiumRequest $request)
    {
        Gate::authorize('join', Condominium::class);

        return new CondominiumResource($this->service->join(
            $request->user(),
            $request->validated('invite_code'),
            $request->validated('property_id'),
        ));
    }

    /**
     * Retira uma casa do condomínio (o residente a sua, o administrador qualquer uma).
     *
     * @param LeaveCondominiumRequest $request Dados validados (property_id).
     * @param Condominium $condominium Condomínio.
     * @return \Illuminate\Http\JsonResponse
     */
    public function leave(LeaveCondominiumRequest $request, Condominium $condominium)
    {
        Gate::authorize('leave', $condominium);

        $this->service->leave($condominium, $request->user(), $request->validated('property_id'));

        return response()->json(['message' => 'A casa foi retirada do condomínio.']);
    }
}