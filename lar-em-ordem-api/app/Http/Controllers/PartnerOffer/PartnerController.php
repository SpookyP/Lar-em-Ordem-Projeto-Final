<?php

namespace App\Http\Controllers\PartnerOffer;

use App\Http\Controllers\Controller;
use App\Models\User\User;
use App\Http\Requests\PartnerOffer\StorePartnerRequest;
use App\Http\Requests\PartnerOffer\UpdatePartnerRequest;
use App\Http\Resources\PartnerOffer\PartnerResource;
use App\Models\User\Partner;
use App\Services\PartnerOffer\PartnerService;


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
     * Cria um novo perfil de parceiro associado a um utilizador.
     * 
     * @param StorePartnerRequest $request Dados validados.
     * @return \Illuminate\Http\JsonResponse Devolve o recurso criado com o status 201 (Created).
     */
    public function store(StorePartnerRequest $request)
    {
        //Sanctum ainda não está ativo e ainda nao tenho policies
        // $this->authorize('create', Partner::class); 

        $fakeUser = User::first(); //remover 


        //$partner = $this->service->create($request->validated(), $request->user()); 
        $partner = $this->service->create($request->validated(), $fakeUser); //remover
        
        return (new PartnerResource($partner))
            ->response()
            ->setStatusCode(201);
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
    

    /**
     * Atualiza os dados de um parceiro existente.
     *
     * @param UpdatePartnerRequest $request Dados validados.
     * @param Partner $partner Instância do parceiro.
     * @return PartnerResource Devolve o parceiro atualizado.
     */
    public function update(UpdatePartnerRequest $request, Partner $partner)
    {
        //$this->authorize('update', $partner);

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
        //$this->authorize('delete', $partner);

        $this->service->delete($partner);

          return response()->json(['message' => 'Partner removed']);

    }
}
