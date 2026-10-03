<?php

namespace App\Http\Controllers\PartnerOffer;

use App\Http\Controllers\Controller;
use App\Http\Requests\PartnerOffer\StoreOfferRequest;
use App\Http\Requests\PartnerOffer\UpdateOfferRequest;
use App\Http\Resources\PartnerOffer\OfferResource;
use App\Models\Offer\Offer;
use App\Models\User\Partner;
use App\Services\PartnerOffer\OfferService;
use App\Services\PartnerOffer\PartnerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;


class OfferController extends Controller
{

    public function __construct(
        private OfferService $service,
        private PartnerService $partnerService,
    ) {}

    /**
     * Cria uma nova oferta associada a um parceiro.
     * 
     * @param StoreOfferRequest $request Dados validados.
     * @return \Illuminate\Http\JsonResponse Devolve o recurso criado com o status 201 (Created).
     */
    public function store(StoreOfferRequest $request)
    {
        Gate::authorize('create', Offer::class);

        $partner = $this->partnerService->getByUser($request->user());
        $offer = $this->service->create($request->validated(), $partner);

        return (new OfferResource($offer))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Devolve os detalhes de uma oferta específica de um parceiro.
     *
     * @param Offer $offer Instância da oferta solicitada (automatico p/ Laravel).
     * @return OfferResource
     */
    public function show(Offer $offer)
    {
        return new OfferResource($this->service->loadWithPartner($offer));
    }


    /**
     * Atualiza os dados de uma oferta existente.
     *
     * @param UpdateOfferRequest $request Dados validados.
     * @param Offer $offer Instância da oferta.
     * @return OfferResource Devolve a oferta atualizada.
     */
    public function update(UpdateOfferRequest $request, Offer $offer)
    {
        Gate::authorize('update', $offer);

        $updatedOffer = $this->service->update($offer, $request->validated());

        return new OfferResource($updatedOffer);    
    }

   /**
     * Remove permanentemente uma oferta do sistema.
     *
     * @param Offer $offer Instância da oferta.
     * @return \Illuminate\Http\JsonResponse Confirmação da eliminação.
     */
    public function destroy(Offer $offer)
    {
        Gate::authorize('delete', $offer);

        $this->service->delete($offer);

        return response()->json(['message' => 'Oferta removida.']);
    }


    /**
     * Lista as ofertas de um parceiro específico.
     * Permite filtrar por estado enviando o parâmetro 'active' (ex: ?active=true) no URL.
     *
     * @param \Illuminate\Http\Request $request O pedido HTTP com os parâmetros de pesquisa.
     * @param \App\Models\User\Partner $partner Instância do parceiro.
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection Coleção de ofertas.
     */
    public function listByPartner(Request $request, Partner $partner)
    {
        
        $active = $request->has('active')
            ? $request->boolean('active')
            : null;

        return OfferResource::collection(
            $this->service->listByPartner($partner, $active)
        );
    }

    /**
     * Devolve todas as ofertas ativas e dentro da validade.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection Coleção de ofertas globais.
     */
    public function listActiveOffers()
    {
        return OfferResource::collection(
            $this->service->listActiveOffers()
        );
    }
}
