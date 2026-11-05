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
     * Documentos do condomínio (administrador), com as categorias para os filtros e o formulário.
     *
     * @param IndexCondominiumDocumentRequest $request Pedido validado (per_page, category_id).
     * @param Condominium $condominium Condomínio.
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(IndexCondominiumDocumentRequest $request, Condominium $condominium)
    {
        Gate::authorize('viewAny', [CondominiumDocument::class, $condominium]);

        return $this->listing($condominium, $request);
    }

    /**
     * Documentos do condomínio da casa ativa (residente), com as categorias para os filtros.
     * Casa sem condomínio: página vazia (mesmo formato).
     *
     * @param IndexCondominiumDocumentRequest $request Pedido validado (per_page, category_id).
     * @param Property $property Casa ativa do residente.
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function indexForProperty(IndexCondominiumDocumentRequest $request, Property $property)
    {
        // reutiliza PropertyPolicy::view → contrato ativo nesta casa
        Gate::authorize('view', $property);

        return $this->listing($property->condominium, $request);
    }

    /**
     * Carrega um documento (multipart). Só o administrador.
     *
     * @param StoreCondominiumDocumentRequest $request Dados validados (com o ficheiro).
     * @param Condominium $condominium Condomínio.
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
     * Corrige os dados do documento (não troca o ficheiro). Só o administrador.
     *
     * @param UpdateCondominiumDocumentRequest $request Dados validados.
     * @param CondominiumDocument $condominiumDocument Documento.
     * @return CondominiumDocumentResource
     */
    public function update(UpdateCondominiumDocumentRequest $request, CondominiumDocument $condominiumDocument)
    {
        Gate::authorize('update', $condominiumDocument);

        return new CondominiumDocumentResource($this->service->update($condominiumDocument, $request->validated()));
    }

    /**
     * Apaga o documento e o ficheiro. Só o administrador.
     *
     * @param CondominiumDocument $condominiumDocument Documento.
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(CondominiumDocument $condominiumDocument)
    {
        Gate::authorize('delete', $condominiumDocument);

        $this->service->delete($condominiumDocument);

        return response()->json(['message' => 'Documento removido.']);
    }

    /**
     * Link temporário para ver/descarregar o ficheiro (administrador ou residentes do prédio).
     *
     * @param CondominiumDocument $condominiumDocument Documento.
     * @return \Illuminate\Http\JsonResponse
     */
    public function download(CondominiumDocument $condominiumDocument)
    {
        Gate::authorize('view', $condominiumDocument);

        return response()->json(['data' => $this->service->downloadLink($condominiumDocument)]);
    }

    /**
     * Lista paginada + catálogo de categorias, no mesmo pedido.
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