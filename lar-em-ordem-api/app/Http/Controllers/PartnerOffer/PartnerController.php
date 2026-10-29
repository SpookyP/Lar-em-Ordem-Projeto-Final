<?php

namespace App\Http\Controllers\PartnerOffer;

use App\Http\Controllers\Controller;
use App\Http\Resources\PartnerOffer\PartnerResource;
use App\Models\User\Partner;
use App\Services\PartnerOffer\PartnerService;
use Illuminate\Http\Request;

class PartnerController extends Controller
{
    public function __construct(private PartnerService $service) {}

   /**
     * Lista todos os parceiros.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection Coleção de parceiros.
     */
    public function index()
    {
        $partners= $this->service->list();

        return PartnerResource::collection($partners);
    }
   
    /**
     * Devolve os detalhes de um parceiro específico, com as suas ofertas em vigor.
     *
     * @param Partner $partner Instância do parceiro solicitada.
     * @return PartnerResource
     */
    public function show(Partner $partner)
    {
        return new PartnerResource($this->service->loadWithOffers($partner));
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
