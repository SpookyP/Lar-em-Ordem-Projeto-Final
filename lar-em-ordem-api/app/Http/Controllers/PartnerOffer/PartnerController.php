<?php

namespace App\Http\Controllers\PartnerOffer;

use App\Http\Controllers\Controller;
use App\Http\Resources\PartnerOffer\PartnerResource;
use App\Http\Requests\PerPageRequest;
use App\Http\Requests\PartnerOffer\IndexPartnerRequest;
use App\Models\User\Partner;
use App\Services\PartnerOffer\PartnerService;

class PartnerController extends Controller
{
    public function __construct(private PartnerService $service) {}

    /**
     * Lista os parceiros por ordem alfabética, paginados, com o nº de ofertas em vigor;
     * ?search= filtra pelo nome ou pela descrição.
     *
     * @param IndexPartnerRequest $request Pedido validado (per_page, search).
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(IndexPartnerRequest $request)
    {
        return PartnerResource::collection($this->service->list(
            $request->integer('per_page', 5),
            $request->validated('search'),
        ));
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
}
