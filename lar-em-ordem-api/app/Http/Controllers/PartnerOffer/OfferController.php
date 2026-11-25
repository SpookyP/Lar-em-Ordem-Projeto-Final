<?php

namespace App\Http\Controllers\PartnerOffer;

use App\Http\Controllers\Controller;
use App\Http\Requests\PartnerOffer\StoreOfferRequest;
use App\Http\Requests\PartnerOffer\UpdateOfferRequest;
use App\Http\Requests\PartnerOffer\IndexOfferRequest;
use App\Http\Requests\PartnerOffer\RecommendedOfferRequest;
use App\Http\Resources\PartnerOffer\OfferResource;
use App\Models\Offer\Offer;
use App\Services\Auth\ProfileService;
use App\Services\PartnerOffer\OfferService;
use Illuminate\Support\Facades\Gate;


class OfferController extends Controller
{

    public function __construct(
        private OfferService $service,
        private ProfileService $profiles,
    ) {}

    /**
     * Lists the authenticated partner's offers, paginated, with an optional status filter
     * (?status=current|scheduled|expired|inactive; without status it returns all of them).
     *
     * @param IndexOfferRequest $request HTTP request with the validated status and page size.
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection Paginated offer collection.
     */
    public function index(IndexOfferRequest $request)
    {
        $partner = $this->profiles->get($request->user(), 'partner');

        return OfferResource::collection(
            $this->service->listByPartner($partner, $request->integer('per_page', 5), $request->validated('status'))
        );
    }

    /**
     * Creates a new offer for the authenticated user's partner profile.
     *
     * @param StoreOfferRequest $request Validated data.
     * @return \Illuminate\Http\JsonResponse The created resource with status 201 (Created).
     */
    public function store(StoreOfferRequest $request)
    {
        Gate::authorize('create', Offer::class);

        $partner = $this->profiles->get($request->user(), 'partner');
        $offer = $this->service->create($request->validated(), $partner);

        return (new OfferResource($offer))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Returns an offer's details, including the partner who created it.
     * The owning partner always sees it; everyone else only sees current offers (404 otherwise).
     *
     * @param Offer $offer The requested offer.
     * @return OfferResource
     */
    public function show(Offer $offer)
    {
        Gate::authorize('view', $offer);

        return new OfferResource($this->service->loadWithPartner($offer));
    }


    /**
     * Updates an existing offer.
     *
     * @param UpdateOfferRequest $request Validated data.
     * @param Offer $offer The offer.
     * @return OfferResource The updated offer.
     */
    public function update(UpdateOfferRequest $request, Offer $offer)
    {
        Gate::authorize('update', $offer);

        $updatedOffer = $this->service->update($offer, $request->validated());

        return new OfferResource($updatedOffer);    
    }

    /**
     * Removes an offer.
     *
     * @param Offer $offer The offer.
     * @return \Illuminate\Http\JsonResponse Removal confirmation.
     */
    public function destroy(Offer $offer)
    {
        Gate::authorize('delete', $offer);

        $this->service->delete($offer);

        return response()->json(['message' => 'Oferta removida.']);
    }

    /**
     * Returns the current offers of every partner, paginated, with search and a type filter,
     * plus the type catalog (for the filters) in the same request.
     *
     * @param RecommendedOfferRequest $request HTTP request with the validated search, type and page size.
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection Paginated offer collection.
     */
    public function listActiveOffers(RecommendedOfferRequest $request)
    {
        return OfferResource::collection(
            $this->service->listActiveOffers(
                $request->integer('per_page', 5),
                $request->validated('search'),
                $request->integer('category_id') ?: null,
            )
        )->additional(['categories' => $this->service->categories()]);
    }
}
