<?php

namespace App\Http\Controllers\Condominium;

use App\Http\Controllers\Controller;
use App\Http\Requests\Condominium\JoinCondominiumRequest;
use App\Http\Requests\Condominium\LeaveCondominiumRequest;
use App\Http\Requests\Condominium\StoreCondominiumRequest;
use App\Http\Requests\Condominium\UpdateCondominiumRequest;
use App\Http\Requests\Condominium\ClaimCondominiumRequest;
use App\Http\Resources\Condominium\CondominiumResource;
use App\Models\Condominium\Condominium;
use App\Models\Property\Property;
use App\Services\Auth\ProfileService;
use App\Services\Condominium\CondominiumService;
use App\Services\Condominium\CondominiumNoticeService;
use Illuminate\Support\Facades\Gate;
use App\Http\Requests\PerPageRequest;

class CondominiumController extends Controller
{
    public function __construct(
        private CondominiumService $service,
        private ProfileService $profiles,
        private CondominiumNoticeService $noticeService,
    ) {}

    /**
     * Lists the condominiums the user manages.
     *
     * @param PerPageRequest $request HTTP request with the validated page size.
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(PerPageRequest $request)
    {
        Gate::authorize('viewAny', Condominium::class);

        return CondominiumResource::collection(
            $this->service->listFor($request->user(), $request->integer('per_page', 5))
        );
    }

    /**
     * Creates a condominium managed by the authenticated admin.
     *
     * @param StoreCondominiumRequest $request Validated data.
     * @return \Illuminate\Http\JsonResponse 201 (Created).
     */
    public function store(StoreCondominiumRequest $request)
    {
        Gate::authorize('create', Condominium::class);

        $admin = $this->profiles->get($request->user(), 'condominium_admin');

        return (new CondominiumResource($this->service->create($request->validated(), $admin)))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Returns a condominium to its admin: linked fractions, invite code and current notices
     * (with how many residents have seen each one). Residents use GET /properties/{id}/condominium.
     *
     * @param Condominium $condominium Condominium.
     * @return CondominiumResource
     */
    public function show(Condominium $condominium)
    {
        Gate::authorize('view', $condominium);

        $this->service->loadFull($condominium, withProperties: true);
        $this->noticeService->loadCurrent($condominium, canManage: true);

        return new CondominiumResource($condominium);
    }

    /**
     * Read-only condominium page for the resident: the active property's condominium and its current notices.
     * A property with no condominium gets data null.
     *
     * @param Property $property The resident's active property.
     * @return CondominiumResource|\Illuminate\Http\JsonResponse
     */
    public function showForProperty(Property $property)
    {
        // reuses PropertyPolicy::view → active contract on this property
        Gate::authorize('view', $property);

        $condominium = $property->condominium;

        if (! $condominium) {
            return response()->json(['data' => null]);
        }

        $this->service->loadFull($condominium);
        $this->noticeService->loadCurrent($condominium, canManage: false);

        return new CondominiumResource($condominium);
    }

    /**
     * Updates the condominium (and/or generates a new invite code). Admin only.
     *
     * @param UpdateCondominiumRequest $request Validated data.
     * @param Condominium $condominium Condominium.
     * @return CondominiumResource
     */
    public function update(UpdateCondominiumRequest $request, Condominium $condominium)
    {
        Gate::authorize('update', $condominium);

        return new CondominiumResource($this->service->update($condominium, $request->validated()));
    }

    /**
     * Removes the condominium and unlinks its properties. Admin only.
     *
     * @param Condominium $condominium Condominium.
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Condominium $condominium)
    {
        Gate::authorize('delete', $condominium);

        $this->service->delete($condominium);

        return response()->json(['message' => 'Condomínio removido.']);
    }

    /**
     * Links one of the resident's properties to the condominium of the invite code.
     *
     * @param JoinCondominiumRequest $request Validated data (invite_code, property_id).
     * @return CondominiumResource
     */
    public function join(JoinCondominiumRequest $request)
    {
        Gate::authorize('join', Condominium::class);

        return new CondominiumResource($this->service->join(
            $request->user(),
            $request->validated('invite_code'),
            $request->validated('property_id'),
        ));
    }

    /**
     * Removes a property from the condominium (residents their own, the admin any of them).
     *
     * @param LeaveCondominiumRequest $request Validated data (property_id).
     * @param Condominium $condominium Condominium.
     * @return \Illuminate\Http\JsonResponse
     */
    public function leave(LeaveCondominiumRequest $request, Condominium $condominium)
    {
        Gate::authorize('leave', $condominium);

        $this->service->leave($condominium, $request->user(), $request->validated('property_id'));

        return response()->json(['message' => 'A casa foi retirada do condomínio.']);
    }

    /**
     * Generates the transfer code (only shown in this response). Current admin only.
     *
     * @param Condominium $condominium Condominium to transfer.
     * @return \Illuminate\Http\JsonResponse 201 (Created).
     */
    public function startTransfer(Condominium $condominium)
    {
        Gate::authorize('update', $condominium);

        $code = $this->service->startTransfer($condominium);

        return response()->json([
            'message' => 'Entregue este código ao novo administrador. Não volta a ser mostrado.',
            'data'    => [
                'transfer_code'       => $code,
                'transfer_expires_at' => $condominium->transfer_expires_at,
            ],
        ], 201);
    }

    /**
     * Cancels the pending transfer. Current admin only.
     *
     * @param Condominium $condominium Condominium.
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelTransfer(Condominium $condominium)
    {
        Gate::authorize('update', $condominium);

        $this->service->cancelTransfer($condominium);

        return response()->json(['message' => 'Transferência cancelada.']);
    }

    /**
     * The new admin accepts the transfer with the code they received.
     *
     * @param ClaimCondominiumRequest $request Validated data (transfer_code).
     * @return CondominiumResource The condominium, now managed by whoever accepted.
     */
    public function claim(ClaimCondominiumRequest $request)
    {
        Gate::authorize('claim', Condominium::class);

        $admin = $this->profiles->get($request->user(), 'condominium_admin');

        return new CondominiumResource($this->service->claim($request->validated('transfer_code'), $admin));
    }
}