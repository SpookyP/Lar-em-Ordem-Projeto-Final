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
     * Lists partners alphabetically, paginated, with their number of current offers;
     * ?search= filters by name or description.
     *
     * @param IndexPartnerRequest $request Validated request (per_page, search).
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
     * Returns a partner with one page of their current offers (data.offers)
     * and that page's pagination in offers_meta (?page=&per_page= for the offers).
     *
     * @param PerPageRequest $request HTTP request with the validated page size.
     * @param Partner $partner The requested partner.
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
