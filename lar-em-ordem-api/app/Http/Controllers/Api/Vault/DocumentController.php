<?php

namespace App\Http\Controllers\Api\Vault;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vault\StoreDocumentRequest;
use App\Http\Requests\Vault\UpdateDocumentRequest;
use App\Http\Resources\Vault\DocumentResource;
use App\Models\Property\Property;
use App\Models\Vault\Document;
use App\Services\Vault\DocumentService;
use App\Services\FileStorage\FileStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DocumentController extends Controller
{
    public function __construct(
        private readonly DocumentService $documentService,
        private readonly FileStorageService $fileStorageService
    ) {}

    public function index(Property $property): AnonymousResourceCollection
    {
        $this->authorize('view', $property);

        $documents = Document::where('property_id', $property->id)
            ->with('category')
            ->orderBy('created_at', 'desc')
            ->get();

        return DocumentResource::collection($documents);
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

        $document->load('category');

        return response()->json([
            'message' => 'Documento guardado com sucesso.',
            'data' => new DocumentResource($document)
        ], 201);
    }

    public function show(Document $document): DocumentResource
    {
        // Garante que só quem tem relação com a casa vê o documento
        $this->authorize('view', $document->property);

        // Carrega a relação da categoria para o frontend ter o nome
        $document->load('category');

        return new DocumentResource($document);
    }

    public function update(UpdateDocumentRequest $request, Document $document): JsonResponse
    {
        $this->authorize('update', $document->property);

        $validatedData = $request->validated();
        $file = $request->file('file');

        $updatedDocument = $this->documentService->updateDocument($document, $validatedData, $file);

        $updatedDocument->load('category');

        return response()->json([
            'message' => 'Documento atualizado com sucesso.',
            'data' => new DocumentResource($updatedDocument)
        ], 200);
    }

    public function download(Document $document): JsonResponse
    {
        $this->authorize('view', $document->property);

        if (!$document->file_id) {
            return response()->json([
                'message' => 'Este documento não tem nenhum ficheiro anexado.'
            ], 404);
        }

        $file = \App\Models\File\File::findOrFail($document->file_id);

        // Pede ao FileStorageService o link pre-assinado do Cloudflare R2
        $url = $this->fileStorageService->getSecureUrl($file);

        return response()->json([
            'url' => $url,
        ]);
    }

    public function destroy(Document $document): JsonResponse
    {
        $this->authorize('update', $document->property);

        $this->documentService->deleteDocument($document);

        return response()->json(['message' => 'Documento e respetivos alertas eliminados com sucesso.']);
    }
}
