<?php

namespace App\Http\Controllers\ServiceProvider;

use App\Http\Controllers\Controller;
use App\Http\Requests\PerPageRequest;
use App\Http\Requests\ServiceProvider\IndexAssistanceRequestRequest;
use App\Http\Requests\ServiceProvider\AvailableAssistanceRequestRequest;
use App\Http\Requests\ServiceProvider\StoreAssistanceRequestRequest;
use App\Http\Requests\ServiceProvider\UpdateAssistanceRequestRequest;
use App\Http\Resources\ServiceProvider\AssistanceRequestResource;
use App\Models\ServiceProvider\AssistanceRequest;
use App\Models\ServiceProvider\RequestAttachment;
use App\Services\Auth\ProfileService;
use App\Services\FileStorage\FileStorageService;
use App\Services\ServiceProvider\AssistanceRequestService;
use Illuminate\Support\Facades\Gate;

class AssistanceRequestController extends Controller
{
    public function __construct(
        private AssistanceRequestService $service,
        private ProfileService $profiles,
    ) {}

     /**
      * Lists the authenticated user's assistance requests for one property (?property_id=, required), paginated.
      * Can be filtered with 'status' (e.g. ?status=open) and sized with ?per_page=.
      *
      * @param IndexAssistanceRequestRequest $request HTTP request with the validated filter.
      * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection Request collection.
      */
    public function index(IndexAssistanceRequestRequest $request)
    {
        return AssistanceRequestResource::collection(
            $this->service->listRequestsByUser(
                $request->user(),
                $request->validated('property_id'),
                $request->validated('status'),
                $request->integer('per_page', 5),
            )
        );
    }

    /**
     * Opens a new assistance request for one of the authenticated resident's properties.
     *
     * @param StoreAssistanceRequestRequest $request Validated data.
     * @return \Illuminate\Http\JsonResponse The created resource with status 201.
     */
    public function store(StoreAssistanceRequestRequest $request)
    {
        Gate::authorize('create', AssistanceRequest::class);

        $assistanceRequest = $this->service->create($request->validated(), $request->user());

        return (new AssistanceRequestResource($assistanceRequest))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Returns an assistance request's details, including the property and the category.
     * The owner also gets the proposals received, paginated (?page=&per_page=),
     * with the pagination in proposals_meta.
     *
     * @param PerPageRequest $request HTTP request with the validated page size.
     * @param AssistanceRequest $assistanceRequest The request.
     * @return AssistanceRequestResource
     */
    public function show(PerPageRequest $request, AssistanceRequest $assistanceRequest)
    {
        Gate::authorize('view', $assistanceRequest);

        $proposals = $this->service->loadForShow($assistanceRequest, $request->user(), $request->integer('per_page', 5));
        $resource = new AssistanceRequestResource($assistanceRequest);

        return $proposals ? $resource->additional(['proposals_meta' => [
            'current_page' => $proposals->currentPage(),
            'last_page'    => $proposals->lastPage(),
            'per_page'     => $proposals->perPage(),
            'total'        => $proposals->total(),
        ]]) : $resource;
    }
    
    /**
     * Updates an assistance request while it is open, images included.
     * With images, send multipart as POST with '_method=PATCH'.
     *
     * @param UpdateAssistanceRequestRequest $request Validated data.
     * @param AssistanceRequest $assistanceRequest The request.
     * @return AssistanceRequestResource The updated request.
     */
    public function update(UpdateAssistanceRequestRequest $request, AssistanceRequest $assistanceRequest)
    {
        Gate::authorize('update', $assistanceRequest);

        return new AssistanceRequestResource(
            $this->service->update($assistanceRequest, $request->validated())
        );
    }

    /**
     * Cancels an open assistance request.
     *
     * @param AssistanceRequest $assistanceRequest The request.
     * @return AssistanceRequestResource The cancelled request.
     */
    public function cancel(AssistanceRequest $assistanceRequest)
    {
        Gate::authorize('cancel', $assistanceRequest);

        return new AssistanceRequestResource($this->service->cancel($assistanceRequest));
    }

    /**
     * Lists the open requests available to the authenticated service provider
     * (in their categories and zones), with optional text and location search.
     *
     * @param AvailableAssistanceRequestRequest $request HTTP request with the validated search and page size.
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection Paginated request collection.
     */
    public function available(AvailableAssistanceRequestRequest $request)
    {
        Gate::authorize('viewAvailable', AssistanceRequest::class);

        $provider = $this->profiles->get($request->user(), 'service_provider');

        return AssistanceRequestResource::collection(
            $this->service->listAvailableFor(
                $provider,
                $request->integer('per_page', 5),
                $request->validated('search'),
                $request->validated('location'),
            )
        );
    }
    
    /**
     * A photo or file attached to a request: whoever may see the request may see its attachments.
     */
    public function attachmentContent(RequestAttachment $requestAttachment, FileStorageService $files)
    {
        abort_if($requestAttachment->assistanceRequest === null, 404);

        Gate::authorize('view', $requestAttachment->assistanceRequest);

        return $files->contentResponse($requestAttachment->file);
    }
}