<?php

namespace App\Services\Vault;

use App\Models\Vault\Document;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class DocumentService
{
    protected PdfExtractionService $pdfExtractionService;

    public function __construct(PdfExtractionService $pdfExtractionService)
    {
        $this->pdfExtractionService = $pdfExtractionService;
    }

    public function processAndSaveDocument(array $validatedData, UploadedFile $file): Document
    {
        $fileId = (string) Str::uuid();
        $file->storeAs('vault_documents', $fileId, 'local');
        $absolutePath = Storage::disk('local')->path('vault_documents/' . $fileId);

        // Extração via Python
        $extractedData = $this->pdfExtractionService->extractInfo($absolutePath);

        $issueDate = $validatedData['issue_date']
            ?? $extractedData['issue_date']
            ?? now()->toDateString();

        $expirationDate = $validatedData['expiration_date']
            ?? $extractedData['expiration_date']
            ?? null;

        return Document::create([
            'property_id' => $validatedData['property_id'],
            'document_category_id' => $validatedData['document_category_id'],
            'name' => $file->getClientOriginalName(),
            'description' => $validatedData['description'],
            'file_id' => $fileId,
            'issue_date' => $issueDate,
            'expiration_date' => $expirationDate,
            'extracted_data' => $extractedData,
        ]);
    }
}
