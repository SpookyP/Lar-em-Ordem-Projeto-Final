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

    public function index()
    {
        $partners= $this->service->listActive();

        return PartnerResource::collection($partners);
    }

    
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

   
    public function show(Partner $partner)
    {
        return new PartnerResource($partner);
    }


     /*
    public function showWithOffers(Partner $partner): PartnerResource
    {
        return new PartnerResource($this->service->loadWithOffers($partner));
    }
    */

    
    public function update(UpdatePartnerRequest $request, Partner $partner)
    {
        //$this->authorize('update', $partner);

        $updatedPartner = $this->service->update($partner, $request->validated());

        return new PartnerResource($updatedPartner);    
    }

 
    public function destroy(Partner $partner)
    {
        //$this->authorize('delete', $partner);

        $this->service->delete($partner);

          return response()->json(['message' => 'Partner removed']);

    }

   
}
