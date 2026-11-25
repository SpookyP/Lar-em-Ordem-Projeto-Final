<?php

namespace App\Services\ServiceProvider;

use App\Models\Property\Property;
use App\Models\ServiceProvider\AssistanceRequest;
use App\Models\User\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use App\Models\User\ServiceProvider as ServiceProviderModel;
use Illuminate\Support\Arr;
use App\Models\User\Resident;
use App\Services\Privacy\PersonalDataEraser;
use App\Support\Search;

class AssistanceRequestService
{
    public function __construct(private RequestAttachmentService $attachmentService) {}

     /**
      * Returns a user's requests for one property, newest first, paginated.
      *
      * @param User $user Owner of the requests.
      * @param string $propertyId Property the resident is in.
      * @param string|null $status (Optional) Status filter.
      * @param int $perPage Requests per page.
      * @return LengthAwarePaginator
      */
    public function listRequestsByUser(User $user, string $propertyId, ?string $status = null, int $perPage = 5): LengthAwarePaginator
    {
        return AssistanceRequest::where('resident_id', $user->resident?->id)
            ->where('property_id', $propertyId)
            ->when($status, fn ($q) => $q->where('status', $status))
            ->with(['property.address', 'problemCategory'])
            ->withCount(['proposals as pending_proposals_count' => fn ($q) => $q->stillValid()])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }
    
     /**
      * Returns the requests available to a service provider, newest first, paginated.
      * The search runs on top of what the provider can already see (availableFor).
      *
      * @param ServiceProviderModel $provider Provider to look up requests for.
      * @param int $perPage Requests per page.
      * @param string|null $search Text to search in the title or description.
      * @param string|null $location Text to search in the property's locality, county or district.
      * @return LengthAwarePaginator
      */
    public function listAvailableFor(
        ServiceProviderModel $provider,
        int $perPage = 5,
        ?string $search = null,
        ?string $location = null,
    ): LengthAwarePaginator {
        
        $search   = trim((string) $search);
        $location = trim((string) $location);

        return AssistanceRequest::availableFor($provider)
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('title', 'like', Search::contains($search))
                ->orWhere('description', 'like', Search::contains($search))))
            ->when($location !== '', fn ($q) => $q->whereHas('property.address', fn ($a) => $a
                ->where('location', 'like', Search::contains($location))
                ->orWhere('county', 'like', Search::contains($location))
                ->orWhere('district', 'like', Search::contains($location))))
            ->with(['property.address', 'problemCategory'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Opens an assistance request for one of the user's properties, with optional images (2 at most).
     * The property needs an active contract of the user.
     * Runs in a transaction: if an image upload fails, the request is not created.
     *
     * @param array $data Validated data for the new request (may include 'images').
     * @param User $owner Resident opening the request.
     * @return AssistanceRequest
     */
    public function create(array $data, User $owner): AssistanceRequest
    {
        $property = Property::findOrFail($data['property_id']);

        // reuses PropertyPolicy::view → active contract
        if (Gate::forUser($owner)->denies('view', $property)) {
            throw ValidationException::withMessages([
                'property_id' => 'Só pode abrir pedidos para casas onde tem um contrato ativo.',
            ]);
        }

        $assistanceRequest = DB::transaction(function () use ($data, $owner) {
            $assistanceRequest = AssistanceRequest::create(Arr::except($data, 'images') + [
                'resident_id' => $owner->resident->id,
            ]);

            $this->attachmentService->sync($assistanceRequest, $data['images'] ?? []);

            return $assistanceRequest;
        });

        return $this->loadFull($assistanceRequest);
    }

    /**
     * Updates a request and its images (adds 'images', removes 'remove_attachment_ids').
     * Only while it is open. Runs in a transaction.
     *
     * @param AssistanceRequest $assistanceRequest Request to update.
     * @param array $data Validated data for the update.
     * @return AssistanceRequest
     */
    public function update(AssistanceRequest $assistanceRequest, array $data): AssistanceRequest
    {
        $this->ensureOpen($assistanceRequest, 'Só é possível editar pedidos abertos.');

        DB::transaction(function () use ($assistanceRequest, $data) {
            $assistanceRequest->update(Arr::except($data, ['images', 'remove_attachment_ids']));

            $this->attachmentService->sync(
                $assistanceRequest,
                $data['images'] ?? [],
                $data['remove_attachment_ids'] ?? [],
            );
        });

        return $this->loadFull($assistanceRequest->refresh());
    }
    
    /**
     * Cancels an open request and rejects the proposals that were still pending.
     * Runs in a transaction.
     *
     * @param AssistanceRequest $assistanceRequest Request to cancel.
     * @return AssistanceRequest
     */
    public function cancel(AssistanceRequest $assistanceRequest): AssistanceRequest
    {
        $this->ensureOpen($assistanceRequest, 'Só é possível cancelar pedidos abertos.');

        DB::transaction(function () use ($assistanceRequest) {
            $assistanceRequest->proposals()
                ->where('status', 'pending')
                ->update(['status' => 'rejected', 'response_date' => now()]);

            $assistanceRequest->update([
                'status'    => 'cancelled',
                'closed_at' => now(),
            ]);
        });

        return $this->loadFull($assistanceRequest);
    }

    /**
     * Loads the request for the detail screen. When the viewer is the owner, it also loads the proposals
     * received (with the provider), newest first and paginated: the page goes in the
     * 'proposals' relation (the resource shows it in data.proposals) and the paginator is returned for the meta.
     *
     * @param AssistanceRequest $assistanceRequest The request.
     * @param User $viewer User viewing the request.
     * @param int $perPage Proposals per page.
     * @return LengthAwarePaginator|null Proposal paginator, or null when the viewer can't see them.
     */
    public function loadForShow(AssistanceRequest $assistanceRequest, User $viewer, int $perPage = 5): ?LengthAwarePaginator
    {
        $this->loadFull($assistanceRequest);

        // the provider gets their own proposal on this request (or null);
        $provider = $viewer->service_provider;
        if ($provider && ! $assistanceRequest->isOwnedBy($viewer)) {
            $assistanceRequest->setRelation('myProposal', $assistanceRequest->proposals()
                ->where('service_provider_id', $provider->id)
                ->first());
        }

        if (! Gate::forUser($viewer)->allows('viewProposals', $assistanceRequest)) {
            return null;
        }

        $proposals = $assistanceRequest->proposals()
            ->with('serviceProvider')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        $assistanceRequest->setRelation('proposals', $proposals->getCollection());

        return $proposals;
    }
    
     /**
      * Loads a request's property (with address), category and accepted proposal (with the provider).
      *
      * @param AssistanceRequest $assistanceRequest The request.
      * @return AssistanceRequest The same instance, with the relations loaded.
      */
    public function loadFull(AssistanceRequest $assistanceRequest): AssistanceRequest
    {
        return $assistanceRequest->load([
            'property.address',
            'problemCategory',
            'acceptedProposal.serviceProvider',
            'attachments.file',
        ]);
    }

    /**
     * Makes sure the request is open; otherwise throws a 422 with the given message.
     *
     * @param AssistanceRequest $assistanceRequest The request.
     * @param string $message Error message to return.
     * @return void
     */
    private function ensureOpen(AssistanceRequest $assistanceRequest, string $message): void
    {
        if (! $assistanceRequest->isOpen()) {
            throw ValidationException::withMessages([
                'status' => $message,
            ]);
        }
    }

    /**
     * The property was deleted: its open requests are cancelled and
     * their pending proposals are rejected.
     * Closed requests stay as they are (history for the resident and the providers).
     *
     * @param Property $property The deleted property.
     * @return void
     */
    public function cancelOpenForProperty(Property $property): void
    {
        AssistanceRequest::where('property_id', $property->id)
            ->where('status', 'open')
            ->get()
            ->each(function (AssistanceRequest $request) {
                $request->proposals()
                    ->where('status', 'pending')
                    ->update(['status' => 'rejected', 'response_date' => now()]);

                $request->update(['status' => 'cancelled', 'closed_at' => now()]);
            });
    }

    /**
     * Erases a resident's data under the GDPR.
     * The photos and the text the resident wrote are always erased.
     * A request that received proposals is kept (anonymised) so the service providers
     * keep their history: if it is still open, it is cancelled and its pending proposals are rejected.
     * A request with no proposals is no use to anyone and is deleted.
     */
    public function eraseForResident(Resident $resident): void
    {
        AssistanceRequest::withTrashed()
            ->where('resident_id', $resident->id)
            ->with('attachments')
            ->withCount('proposals')
            ->get()
            ->each(function (AssistanceRequest $request) {
                $this->attachmentService->sync($request, [], $request->attachments->modelKeys());

                if ($request->proposals_count === 0) {
                    $request->forceDelete();

                    return;
                }

                if ($request->isOpen()) {
                    $request->proposals()
                        ->where('status', 'pending')
                        ->update(['status' => 'rejected', 'response_date' => now()]);

                    $request->forceFill(['status' => 'cancelled', 'closed_at' => now()]);
                }

                $request->forceFill([
                    'title'       => PersonalDataEraser::REMOVED_REQUEST,
                    'description' => '',
                ])->save();
            });
    }
}