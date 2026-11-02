<?php

namespace App\Services\Vault;

use App\Models\Vault\Document;
use App\Services\FileStorage\FileStorageService;
use Illuminate\Http\UploadedFile;

class DocumentService
{
    public function __construct(
        protected PdfExtractionService $pdfExtractionService,
        protected FileStorageService $fileStorageService,
        protected DocumentExpiryService $documentExpiryService
    ) {}

    public function processAndSaveDocument(array $validatedData, ?\Illuminate\Http\UploadedFile $file = null): Document
    {
        $extractedData = [];
        $fileRecordId = null;
        $originalFileName = null;
        $fileHash = null;

        if ($file) {
            $extractedData = $this->pdfExtractionService->extractInfo($file->getRealPath());

            // Calcula o hash para guardar na BD
            $fileHash = hash_file('sha256', $file->getRealPath());

            $user = auth()->user();
            $fileRecord = $this->fileStorageService->uploadUserFile(
                $user, $file, 'vault_documents', $validatedData['name'] ?? null
            );

            $fileRecordId = $fileRecord->id;
            $originalFileName = $file->getClientOriginalName();
        }

        // Hierarquia de datas: Input manual > Detetado pelo Python > Fallback
        $issueDate = $validatedData['issue_date']
            ?? ($extractedData['issue_date'] ?? $extractedData['dados']['data_emissao'] ?? null)
            ?? now()->toDateString();

        $expirationDate = $validatedData['expiration_date']
            ?? ($extractedData['expiration_date'] ?? $extractedData['dados']['data_validade'] ?? null);

        // Nome inteligente: Input manual > Python > Nome original do ficheiro > Fallback de segurança
        $documentName = $validatedData['name']
            ?? ($extractedData['suggested_title'] ?? $extractedData['dados']['tipo_documento'] ?? null)
            ?? $originalFileName
            ?? 'Documento ' . now()->format('Y-m-d');

        // Criação do documento na base de dados
        $document = Document::create([
            'property_id' => $validatedData['property_id'],
            'document_category_id' => $validatedData['document_category_id'],
            'name' => $documentName,
            'description' => $validatedData['description'] ?? null,
            'file_id' => $fileRecordId,
            'file_hash' => $fileHash,
            'issue_date' => $issueDate,
            'expiration_date' => $expirationDate,
            'extracted_data' => !empty($extractedData) ? $extractedData : null,
        ]);

        $this->documentExpiryService->checkSingleDocument($document);

        return $document;
    }
}
