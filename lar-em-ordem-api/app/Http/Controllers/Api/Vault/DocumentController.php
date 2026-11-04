<?php

namespace App\Http\Controllers\Api\Vault;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vault\UpdateDocumentRequest;
use App\Models\Property\Property;
use App\Models\Vault\Document;
use App\Services\Vault\DocumentService;
use App\Http\Requests\Vault\StoreDocumentRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function __construct(
        private readonly DocumentService $documentService
    ) {}

    public function index(Property $property)
    {
        $this->authorize('view', $property);

        $documents = Document::where('property_id', $property->id)
            ->with('category')
            ->orderBy('created_at', 'desc')
            ->get();

        return \App\Http\Resources\Vault\DocumentResource::collection($documents);
    }

    public function show(Document $document): JsonResponse
    {
        // Garante que só quem tem relação com a casa vê o documento
        $this->authorize('view', $document->property);

        // Carrega a relação da categoria para o frontend ter o nome
        $document->load('category');

        return response()->json([
            'data' => $document
        ]);
    }

    public function update(UpdateDocumentRequest $request, Document $document): JsonResponse
    {
        $this->authorize('update', $document->property);

        $validatedData = $request->validated();
        $file = $request->file('file');

        $updatedDocument = $this->documentService->updateDocument($document, $validatedData, $file);

        return response()->json([
            'message' => 'Documento atualizado com sucesso.',
            'data' => $updatedDocument
        ], 200);
    }

    public function download(Document $document)
    {
        $this->authorize('view', $document->property);

        $path = $document->ficheiro_path ?? ('vault_documents/' . $document->file_id);

        if (!Storage::disk('local')->exists($path)) {
            return response()->json(['message' => 'Ficheiro não encontrado no servidor.'], 404);
        }

        return Storage::disk('local')->download($path, $document->name);
    }

    public function destroy(Document $document): JsonResponse
    {
        $this->authorize('update', $document->property);

        $this->documentService->deleteDocument($document);

        return response()->json(['message' => 'Documento e respetivos alertas eliminados com sucesso.']);
    }

    public function store(StoreDocumentRequest $request, Property $property): JsonResponse
    {
        $this->authorize('update', $property);

        $validatedData = $request->validated();

        $validatedData['property_id'] = $property->id;

        $document = $this->documentService->processAndSaveDocument(
            $validatedData,
            $request->file('file')
        );

        return response()->json([
            'message' => 'Documento guardado com sucesso.',
            'data' => $document
        ], 201);
    }
}
