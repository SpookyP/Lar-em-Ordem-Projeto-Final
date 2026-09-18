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

        $extractedData = $this->pdfService->extractInfo($absolutePath);

        $document = Document::create([
            'housing_id' => $request->validated('housing_id'),
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'extracted_data' => $extractedData,
            'expiration_date' => $extractedData['expiration_date'] ?? null,
        ]);

        return response()->json([
            'message' => 'Document archived and parsed successfully.',
            'data' => $document
        ], 201);
    }
}
