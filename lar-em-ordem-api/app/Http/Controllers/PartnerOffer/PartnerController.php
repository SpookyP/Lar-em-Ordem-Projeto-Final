<?php

namespace App\Http\Controllers\PartnerOffer;

use App\Http\Controllers\Controller;
use App\Http\Resources\PartnerOffer\PartnerResource;
use App\Http\Requests\PerPageRequest;
use App\Models\User\Partner;
use App\Services\PartnerOffer\PartnerService;
use Illuminate\Http\Request;

class PartnerController extends Controller
{
    public function __construct(private PartnerService $service) {}

   /**
     * Lista os parceiros por ordem alfabética, paginados, com o nº de ofertas em vigor.
     *
     * @param PerPageRequest $request O pedido HTTP com o tamanho da página validado.
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(PerPageRequest $request)
    {
        return PartnerResource::collection($this->service->list($request->integer('per_page', 5)));
    }

    /**
     * Devolve um parceiro com uma página das suas ofertas em vigor (data.offers)
     * e a informação dessa paginação em offers_meta (?page=&per_page= das ofertas).
     *
     * @param PerPageRequest $request O pedido HTTP com o tamanho da página validado.
     * @param Partner $partner Instância do parceiro solicitada.
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(PerPageRequest $request, Partner $partner)
    {
        $offers = $this->service->loadWithOffers($partner, $request->integer('per_page', 5));

        return (new PartnerResource($partner))->additional(['offers_meta' => [
            'current_page' => $offers->currentPage(),
            'last_page'    => $offers->lastPage(),
            'per_page'     => $offers->perPage(),
            'total'        => $offers->total(),
        ]]);
    }

    /**
     * Devolve o perfil de parceiro do utilizador autenticado.
     *
     * @param Request $request Pedido com o utilizador autenticado.
     * @return PartnerResource
     */
    public function showMyProfile(Request $request): PartnerResource
    {
        return new PartnerResource($this->service->getByUser($request->user()));
    }
}
