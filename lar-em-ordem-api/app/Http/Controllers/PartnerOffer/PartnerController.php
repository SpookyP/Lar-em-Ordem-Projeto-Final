<?php

namespace App\Http\Controllers\PartnerOffer;

use App\Http\Controllers\Controller;
use App\Http\Requests\PartnerOffer\UpdatePartnerRequest;
use App\Http\Resources\PartnerOffer\PartnerResource;
use App\Models\User\Partner;
use App\Services\PartnerOffer\PartnerService;
use Illuminate\Support\Facades\Gate;
use App\Http\Requests\PartnerOffer\StorePartnerRequest;

class PartnerController extends Controller
{
    public function __construct(private PartnerService $service) {}

    /**
     * Lista todos os parceiros ativos no sistema.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection Coleção de parceiros.
     */
    public function index()
    {
        $partners= $this->service->listActive();

        return PartnerResource::collection($partners);
    }
   
    /**
     * Devolve os detalhes de um parceiro específico.
     *
     * @param Partner $partner Instância do parceiro solicitada.
     * @return PartnerResource
     */
    public function show(Partner $partner)
    {
        return new PartnerResource($partner);
    }


    /**
     * Devolve os detalhes de um parceiro específico carregando em conjunto a sua lista de ofertas.
     *
     * @param Partner $partner Instância do parceiro.
     * @return PartnerResource
     */
    public function showWithOffers(Partner $partner): PartnerResource
    {
        return new PartnerResource($this->service->loadWithOffers($partner));
    }
    
    public function store(StorePartnerRequest $request)
    {
        Gate::authorize('create', Partner::class);

        $partner = $this->service->create($request->validated(), $request->user());

        return (new PartnerResource($partner))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Atualiza os dados de um parceiro existente.
     *
     * @param UpdatePartnerRequest $request Dados validados.
     * @param Partner $partner Instância do parceiro.
     * @return PartnerResource Devolve o parceiro atualizado.
     */
    public function update(UpdatePartnerRequest $request, Partner $partner)
    {
        Gate::authorize('update', $partner);

        $updatedPartner = $this->service->update($partner, $request->validated());

        return new PartnerResource($updatedPartner);    
    }

 
    /**
     * Remove permanentemente um parceiro do sistema.
     *
     * @param Partner $partner Instância do parceiro.
     * @return \Illuminate\Http\JsonResponse Confirmação da eliminação.
     */
    public function destroy(Partner $partner)
    {
         Gate::authorize('delete', $partner);

        $this->service->delete($partner);

          return response()->json(['message' => 'Parceiro removido.']);

    }
}
