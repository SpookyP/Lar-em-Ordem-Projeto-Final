<?php

namespace App\Http\Controllers\Condominium;

use App\Http\Controllers\Controller;
use App\Http\Requests\Condominium\StoreCondominiumNoticeRequest;
use App\Http\Requests\Condominium\UpdateCondominiumNoticeRequest;
use App\Http\Resources\Condominium\CondominiumNoticeResource;
use App\Models\Condominium\Condominium;
use App\Models\Condominium\CondominiumNotice;
use App\Models\Property\Property;
use App\Services\Condominium\CondominiumNoticeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Http\Requests\PerPageRequest;

class CondominiumNoticeController extends Controller
{
    public function __construct(private CondominiumNoticeService $service) {}

    /**
     * Condominium notice history (expired ones included). Admin only.
     *
     * @param PerPageRequest $request HTTP request with the validated page size.
     * @param Condominium $condominium Condominium.
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(PerPageRequest $request, Condominium $condominium)
    {
        Gate::authorize('viewAny', [CondominiumNotice::class, $condominium]);

        return CondominiumNoticeResource::collection(
            $this->service->listForAdmin($condominium, $request->integer('per_page', 5))
        );
    }

    /**
     * Publishes a notice and emails it to the residents. Admin only.
     *
     * @param StoreCondominiumNoticeRequest $request Validated data.
     * @param Condominium $condominium Condominium.
     * @return \Illuminate\Http\JsonResponse 201 (Created).
     */
    public function store(StoreCondominiumNoticeRequest $request, Condominium $condominium)
    {
        Gate::authorize('create', [CondominiumNotice::class, $condominium]);

        return (new CondominiumNoticeResource($this->service->create($condominium, $request->validated())))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Edits a notice; if something changed it becomes unread again and the email is sent again. Admin only.
     *
     * @param UpdateCondominiumNoticeRequest $request Validated data.
     * @param CondominiumNotice $condominiumNotice Notice.
     * @return CondominiumNoticeResource
     */
    public function update(UpdateCondominiumNoticeRequest $request, CondominiumNotice $condominiumNotice)
    {
        Gate::authorize('update', $condominiumNotice);

        return new CondominiumNoticeResource($this->service->update($condominiumNotice, $request->validated()));
    }

    /**
     * Deletes a notice. Admin only.
     *
     * @param CondominiumNotice $condominiumNotice Notice.
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(CondominiumNotice $condominiumNotice)
    {
        Gate::authorize('delete', $condominiumNotice);

        $this->service->delete($condominiumNotice);

        return response()->json(['message' => 'Aviso removido.']);
    }

    /**
     * The resident marks the notice as seen.
     *
     * @param Request $request Request with the authenticated user.
     * @param CondominiumNotice $condominiumNotice Notice.
     * @return CondominiumNoticeResource
     */
    public function markRead(Request $request, CondominiumNotice $condominiumNotice)
    {
        Gate::authorize('markRead', $condominiumNotice);

        return new CondominiumNoticeResource($this->service->markRead($condominiumNotice, $request->user()));
    }

    /**
     * Resident bell: current notices of the active property's condominium, minus the dismissed ones, with "read".
     * A property with no condominium gets an empty list.
     *
     * @param Request $request Request with the authenticated user.
     * @param Property $property The resident's active property.
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Http\JsonResponse
     */
    public function bell(Request $request, Property $property)
    {
        // reuses PropertyPolicy::view → active contract on this property
        Gate::authorize('view', $property);

        if (! $property->condominium) {
            return response()->json(['data' => []]);
        }

        return CondominiumNoticeResource::collection(
            $this->service->listForBell($property->condominium, $request->user())
        );
    }

    /**
     * "Mark all as read" in the bell (active property's condominium).
     *
     * @param Request $request Request with the authenticated user.
     * @param Property $property The resident's active property.
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAllRead(Request $request, Property $property)
    {
        Gate::authorize('view', $property);

        $count = $property->condominium
            ? $this->service->markAllRead($property->condominium, $request->user())
            : 0;

        return response()->json([
            'message'       => 'Todos os avisos foram marcados como lidos.',
            'updated_count' => $count,
        ]);
    }

    /**
     * The resident dismisses the notice from their bell (the notice itself is not deleted).
     *
     * @param Request $request Request with the authenticated user.
     * @param CondominiumNotice $condominiumNotice Notice.
     * @return \Illuminate\Http\JsonResponse
     */
    public function dismiss(Request $request, CondominiumNotice $condominiumNotice)
    {
        Gate::authorize('dismiss', $condominiumNotice);

        $this->service->dismiss($condominiumNotice, $request->user());

        return response()->json(['message' => 'Aviso removido das notificações.']);
    }

}