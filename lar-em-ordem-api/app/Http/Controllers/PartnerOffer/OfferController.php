<?php

namespace App\Http\Controllers\PartnerOffer;

use App\Http\Controllers\Controller;
use App\Http\Requests\PartnerOffer\StoreOfferRequest;
use App\Http\Requests\PartnerOffer\UpdateOfferRequest;
use App\Http\Resources\PartnerOffer\OfferResource;
use App\Models\Offer\Offer;
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
     * Lista as ofertas do parceiro autenticado (ativas e inativas).
     * Permite filtrar por estado através do parâmetro 'active' no URL (ex: ?active=true).
     *
     * @param \Illuminate\Http\Request $request O pedido HTTP com o filtro opcional.
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection Coleção de ofertas.
     */
    public function index(Request $request)
    {
        $partner = $this->partnerService->getByUser($request->user());

        $active = $request->has('active')
            ? $request->boolean('active')
            : null;

        return OfferResource::collection(
            $this->service->listByPartner($partner, $active)
        );
    }

    /**
     * Cria uma nova oferta associada ao parceiro do utilizador autenticado.
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
     * Devolve os detalhes de uma oferta, incluindo o parceiro que a criou.
     * O parceiro dono vê sempre; os restantes só veem ofertas em vigor (senão 404).
     *
     * @param Offer $offer Instância da oferta solicitada.
     * @return OfferResource
     */
    public function show(Offer $offer)
    {
        Gate::authorize('view', $offer);

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
     * Remove uma oferta.
     *
     * @param Offer $offer Instância da oferta.
     * @return \Illuminate\Http\JsonResponse Confirmação da remoção.
     */
    public function destroy(Offer $offer)
    {
        Gate::authorize('delete', $offer);

        $this->service->delete($offer);

        return response()->json(['message' => 'Oferta removida.']);
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
