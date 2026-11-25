<?php

namespace App\Http\Controllers\Condominium;

use App\Http\Controllers\Controller;
use App\Http\Requests\Condominium\IndexCondominiumDocumentRequest;
use App\Http\Requests\Condominium\StoreCondominiumDocumentRequest;
use App\Http\Requests\Condominium\UpdateCondominiumDocumentRequest;
use App\Http\Resources\Condominium\CondominiumDocumentResource;
use App\Models\Condominium\Condominium;
use App\Models\Condominium\CondominiumDocument;
use App\Models\Property\Property;
use App\Services\Condominium\CondominiumDocumentService;
use Illuminate\Support\Facades\Gate;

class CondominiumDocumentController extends Controller
{
    public function __construct(private CondominiumDocumentService $service) {}

    /**
     * Condominium documents (admin), with the categories for the filters and the form.
     *
     * @param IndexCondominiumDocumentRequest $request Validated request (per_page, category_id).
     * @param Condominium $condominium Condominium.
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(IndexCondominiumDocumentRequest $request, Condominium $condominium)
    {
        Gate::authorize('viewAny', [CondominiumDocument::class, $condominium]);

        return $this->listing($condominium, $request);
    }

    /**
     * Documents of the active property's condominium (resident), with the categories for the filters.
     * A property with no condominium gets an empty page in the same format.
     *
     * @param IndexCondominiumDocumentRequest $request Validated request (per_page, category_id).
     * @param Property $property The resident's active property.
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function indexForProperty(IndexCondominiumDocumentRequest $request, Property $property)
    {
        // reuses PropertyPolicy::view → active contract on this property
        Gate::authorize('view', $property);

        return $this->listing($property->condominium, $request);
    }

    /**
     * Uploads a document (multipart). Admin only.
     *
     * @param StoreCondominiumDocumentRequest $request Validated data (with the file).
     * @param Condominium $condominium Condominium.
     * @return \Illuminate\Http\JsonResponse 201 (Created).
     */
    public function store(StoreCondominiumDocumentRequest $request, Condominium $condominium)
    {
        Gate::authorize('create', [CondominiumDocument::class, $condominium]);

        return (new CondominiumDocumentResource($this->service->create($condominium, $request->validated())))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Fixes the document's details (the file stays the same). Admin only.
     *
     * @param UpdateCondominiumDocumentRequest $request Validated data.
     * @param CondominiumDocument $condominiumDocument Document.
     * @return CondominiumDocumentResource
     */
    public function update(UpdateCondominiumDocumentRequest $request, CondominiumDocument $condominiumDocument)
    {
        Gate::authorize('update', $condominiumDocument);

        return new CondominiumDocumentResource($this->service->update($condominiumDocument, $request->validated()));
    }

    /**
     * Deletes the document and its file. Admin only.
     *
     * @param CondominiumDocument $condominiumDocument Document.
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(CondominiumDocument $condominiumDocument)
    {
        Gate::authorize('delete', $condominiumDocument);

        $this->service->delete($condominiumDocument);

        return response()->json(['message' => 'Documento removido.']);
    }

    /**
     * The file itself: same rule as the link (administrator and residents of the building).
     */
    public function content(CondominiumDocument $condominiumDocument)
    {
        Gate::authorize('view', $condominiumDocument);

        return $this->service->content($condominiumDocument);
    }

    /**
     * Paginated list plus the category catalog, in the same request.
     */
    private function listing(?Condominium $condominium, IndexCondominiumDocumentRequest $request)
    {
        return CondominiumDocumentResource::collection($this->service->list(
            $condominium,
            $request->validated('category_id'),
            $request->integer('per_page', 5),
        ))->additional(['categories' => $this->service->categories()]);
    }
}   