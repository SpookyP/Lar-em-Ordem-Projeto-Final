<?php

namespace App\Http\Controllers\ServiceProvider;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceProvider\ServiceProviderResource;
use App\Services\Auth\ProfileService;
use App\Services\ServiceProvider\ServiceProviderService;
use Illuminate\Http\Request;

class ServiceProviderController extends Controller
{
    public function __construct(
        private ServiceProviderService $service,
        private ProfileService $profiles,
    ) {}

    /**
     * Returns the authenticated user's service provider profile, with its zones and specialties.
     *
     * @param Request $request Request with the authenticated user.
     * @return ServiceProviderResource
     */
    public function showMyProfile(Request $request)
    {
        $provider = $this->profiles->get($request->user(), 'service_provider');

        return new ServiceProviderResource($this->service->loadFull($provider));
    }
}