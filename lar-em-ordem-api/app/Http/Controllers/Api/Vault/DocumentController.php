<?php

namespace App\Http\Controllers\Api\Vault;

use App\Http\Controllers\Controller;
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

    public function download(Document $document)
    {
        $this->authorize('view', $document->property);

        $path = $document->ficheiro_path ?? ('vault_documents/' . $document->file_id);

        if (!Storage::disk('local')->exists($path)) {
            return response()->json(['message' => 'Ficheiro não encontrado no servidor.'], 404);
        }

        return Storage::disk('local')->download($path, $document->name);
    }

    public function destroy(Document $document)
    {
        $this->authorize('update', $document->property);

        $path = $document->ficheiro_path ?? ('vault_documents/' . $document->file_id);

        if (Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
        }

        $document->delete();

        return response()->json(['message' => 'Documento eliminado com sucesso.']);
    }

    public function store(StoreDocumentRequest $request, Property $property): JsonResponse
    {
        $this->authorize('update', $property);

        $document = $this->documentService->processAndSaveDocument(
            $request->validated(),
            $request->file('file')
        );

        return response()->json([
            'message' => 'Document archived and parsed successfully.',
            'data' => $document
        ], 201);
    }
}
