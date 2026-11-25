<?php

namespace App\Http\Controllers\PartnerOffer;

use App\Http\Controllers\Controller;
use App\Services\PartnerOffer\OfferService;

class OfferCategoryController extends Controller
{
    public function __construct(private OfferService $service) {}

    /**
     * Offer type catalog (for the create/edit offer form).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        return response()->json(['data' => $this->service->categories()]);
    }
}