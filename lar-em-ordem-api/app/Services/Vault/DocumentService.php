<?php

namespace App\Services\Vault;

use App\Models\File\File;
use App\Models\Vault\Document;
use App\Models\Vault\Notification;
use App\Services\FileStorage\FileStorageService;
use Illuminate\Http\UploadedFile;

class DocumentService
{
    public function __construct(
        protected PdfExtractionService $pdfExtractionService,
        protected FileStorageService $fileStorageService,
        protected DocumentExpiryService $documentExpiryService
    ) {}

    public function processAndSaveDocument(array $validatedData, ?UploadedFile $file = null): Document
    {
        $extractedData = [];
        $fileRecordId = null;
        $originalFileName = null;
        $fileHash = null;

        if ($file) {
            [$fileRecordId, $fileHash, $extractedData] = $this->handleFileUpload(
                $file,
                $validatedData['name'] ?? null
            );
            $originalFileName = $file->getClientOriginalName();
        }

        // Hierarquia de datas: Input manual > Detetado pelo Python > Fallback
        $issueDate = $validatedData['issue_date']
            ?? ($extractedData['issue_date'] ?? null)
            ?? now()->toDateString();

        $expirationDate = $validatedData['expiration_date']
            ?? ($extractedData['expiration_date'] ?? null);

        // Nome inteligente: Input manual > Python > Nome original do ficheiro > Fallback de segurança
        $documentName = $validatedData['name']
            ?? ($extractedData['suggested_title'] ?? null)
            ?? $originalFileName
            ?? 'Documento ' . now()->format('Y-m-d');

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

    public function updateDocument(Document $document, array $validatedData, ?UploadedFile $file = null): Document
    {
        $extractedData = $document->extracted_data ?? [];
        $fileRecordId = $document->file_id;
        $fileHash = $document->file_hash;
        $documentName = $validatedData['name'] ?? $document->name;

        if ($file) {
            [$newFileRecordId, $fileHash, $newExtractedData] = $this->handleFileUpload($file, $documentName);
            $extractedData = !empty($newExtractedData) ? $newExtractedData : $extractedData;

            if ($document->file_id) {
                $oldFileRecord = File::find($document->file_id);
                if ($oldFileRecord) {
                    $this->fileStorageService->deleteFile($oldFileRecord);
                }
            }

            $fileRecordId = $newFileRecordId;
        }

        $issueDate = $validatedData['issue_date'] ?? $extractedData['issue_date'] ?? $document->issue_date;
        $expirationDate = $validatedData['expiration_date'] ?? $extractedData['expiration_date'] ?? $document->expiration_date;

        Notification::where('document_id', $document->id)->delete();

        $document->update([
            'document_category_id' => $validatedData['document_category_id'] ?? $document->document_category_id,
            'name' => $documentName,
            'description' => array_key_exists('description', $validatedData)
                ? $validatedData['description']
                : $document->description,
            'file_id' => $fileRecordId,
            'file_hash' => $fileHash,
            'issue_date' => $issueDate,
            'expiration_date' => $expirationDate,
            'extracted_data' => $extractedData,
        ]);

        $this->documentExpiryService->checkSingleDocument($document);

        return $document;
    }

    public function deleteDocument(Document $document): void
    {
        if ($document->file_id) {
            $fileRecord = File::find($document->file_id);
            if ($fileRecord) {
                $this->fileStorageService->deleteFile($fileRecord);
            }
        }

        Notification::where('document_id', $document->id)->delete();

        $document->delete();
    }

    /**
     * Extrai metadados (apenas se for PDF), calcula o hash SHA-256 e guarda o ficheiro.
     *
     * @return array{0: string, 1: string, 2: array}
     */
    private function handleFileUpload(UploadedFile $file, ?string $customName = null): array
    {
        $isPdf = $file->getMimeType() === 'application/pdf'
            || strtolower($file->getClientOriginalExtension()) === 'pdf';

        $extractedData = $isPdf
            ? $this->pdfExtractionService->extractInfo($file->getRealPath())
            : [];

        $fileHash = hash_file('sha256', $file->getRealPath());

        $user = auth()->user();

        if (! $user instanceof \App\Models\User\User) {
            throw new \RuntimeException('Utilizador não autenticado.');
        }

        $fileRecord = $this->fileStorageService->uploadUserFile(
            $user,
            $file,
            'vault_documents',
            $customName
        );

        return [$fileRecord->id, $fileHash, $extractedData];
    }
}
