<?php

namespace App\Http\Controllers\ServiceProvider;

use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceProvider\StoreProposalRequest;
use App\Http\Requests\ServiceProvider\UpdateProposalRequest;
use App\Http\Resources\ServiceProvider\ProposalResource;
use App\Models\ServiceProvider\AssistanceRequest;
use App\Models\ServiceProvider\Proposal;
use App\Services\Auth\ProfileService;
use App\Services\ServiceProvider\ProposalService;
use App\Http\Requests\ServiceProvider\IndexProposalRequest;
use Illuminate\Support\Facades\Gate;

class ProposalController extends Controller
{
    public function __construct(
        private ProposalService $service,
        private ProfileService $profiles,
    ) {}

    /**
     * Lists the proposals sent by the authenticated service provider (optionally by status).
     *
     * @param IndexProposalRequest $request HTTP request with the validated status and page size.
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection Paginated proposal collection.
     */
    public function listProviderProposals(IndexProposalRequest $request)
    {
        Gate::authorize('create', Proposal::class);

        $provider = $this->profiles->get($request->user(), 'service_provider');

        return ProposalResource::collection(
            $this->service->listByProvider(
                $provider,
                $request->integer('per_page', 5),
                $request->validated('status'),
            )
        );
    }

    /**
     * Sends a proposal to a request on behalf of the authenticated service provider.
     *
     * @param StoreProposalRequest $request Validated data.
     * @param AssistanceRequest $assistanceRequest The request being answered.
     * @return \Illuminate\Http\JsonResponse The created resource with status 201 (Created).
     */
    public function store(StoreProposalRequest $request, AssistanceRequest $assistanceRequest)
    {
        Gate::authorize('create', Proposal::class);

        $provider = $this->profiles->get($request->user(), 'service_provider');
        $proposal = $this->service->create($assistanceRequest, $provider, $request->validated());

        return (new ProposalResource($proposal))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Edits a pending proposal. Only the provider who owns it can edit it.
     *
     * @param UpdateProposalRequest $request Validated data.
     * @param Proposal $proposal The proposal.
     * @return ProposalResource The updated proposal.
     */
    public function update(UpdateProposalRequest $request, Proposal $proposal)
    {
        Gate::authorize('update', $proposal);

        return new ProposalResource($this->service->update($proposal, $request->validated()));
    }

    /**
     * Withdraws a pending proposal. Only the provider who owns it can withdraw it.
     *
     * @param Proposal $proposal The proposal.
     * @return ProposalResource The withdrawn proposal.
     */
    public function withdrawByProvider(Proposal $proposal)
    {
        Gate::authorize('withdraw', $proposal);

        return new ProposalResource($this->service->withdraw($proposal));
    }

    /**
     * Accepts a proposal: rejects the other pending ones and sets the request to 'closed'.
     * Only the request owner can accept it.
     *
     * @param Proposal $proposal The proposal.
     * @return ProposalResource The accepted proposal.
     */
    public function acceptByResident(Proposal $proposal)
    {
        Gate::authorize('accept', $proposal);

        return new ProposalResource($this->service->accept($proposal));
    }

    /**
     * Rejects a pending proposal; the request stays open. Only the request owner can reject it.
     *
     * @param Proposal $proposal The proposal.
     * @return ProposalResource The rejected proposal.
     */
    public function rejectByResident(Proposal $proposal)
    {
        Gate::authorize('reject', $proposal);

        return new ProposalResource($this->service->reject($proposal));
    }
}