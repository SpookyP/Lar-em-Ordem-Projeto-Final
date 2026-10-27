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

    public function processAndSaveDocument(array $validatedData, UploadedFile $file): Document
    {
        // Extração inteligente via script Python
        $extractedData = $this->pdfExtractionService->extractInfo($file->getRealPath());

        // Upload para o Cloudflare
        $user = auth()->user();
        $fileRecord = $this->fileStorageService->uploadUserFile(
            $user,
            $file,
            'vault_documents',
            $validatedData['name'] ?? null
        );

        // Hierarquia de datas: Input manual > Detetado pelo Python > Fallback
        $issueDate = $validatedData['issue_date']
            ?? ($extractedData['issue_date'] ?? $extractedData['dados']['data_emissao'] ?? null)
            ?? now()->toDateString();

        $expirationDate = $validatedData['expiration_date']
            ?? ($extractedData['expiration_date'] ?? $extractedData['dados']['data_validade'] ?? null);

        // Nome inteligente sugerido pelo Python ou nome original do ficheiro
        $documentName = $validatedData['name']
            ?? ($extractedData['suggested_title'] ?? $extractedData['dados']['tipo_documento'] ?? null)
            ?? $file->getClientOriginalName();

        // Guardar na variável em vez de fazer return imediato
        $document = Document::create([
            'property_id' => $validatedData['property_id'],
            'document_category_id' => $validatedData['document_category_id'],
            'name' => $documentName,
            'description' => $validatedData['description'] ?? null,
            'file_id' => $fileRecord->id,
            'issue_date' => $issueDate,
            'expiration_date' => $expirationDate,
            'extracted_data' => $extractedData,
        ]);

        // Disparar a verificação de validade
        // Se a data de validade extraída estiver a expirar, a notificação é criada na hora.
        $this->documentExpiryService->checkSingleDocument($document);

        // Devolver o documento
        return $document;
    }
}
