<?php

namespace App\Http\Controllers\Api\Vault;

use App\Http\Controllers\Controller;
use App\Models\Vault\Document;
use App\Services\Vault\PdfExtractionService;
use App\Http\Requests\Vault\StoreDocumentRequest;
use Illuminate\Http\JsonResponse;

class DocumentController extends Controller
{
    public function __construct(
        private readonly PdfExtractionService $pdfService
    ) {}

    public function store(StoreDocumentRequest $request): JsonResponse
    {
        $file = $request->file('file');
        $path = $file->store('vault_documents', 'local');

        $absolutePath = storage_path('app/private/' . $path);

        // Extração ocorre na camada Service
        $extractedData = $this->pdfService->extractInfo($absolutePath);

        $document = Document::create([
            'property_id' => $request->validated('property_id'),
            'document_category_id' => $request->validated('document_category_id'),
            'name' => $file->getClientOriginalName(),
            'description' => $request->validated('description'),
            'file_path' => $path,
            'issue_date' => $extractedData['issue_date'] ?? null,
            'expiration_date' => $extractedData['expiration_date'] ?? null,
            'extracted_data' => $extractedData,
        ]);

        return response()->json([
            'message' => 'Document archived and parsed successfully.',
            'data' => $document
        ], 201);
    }
}
