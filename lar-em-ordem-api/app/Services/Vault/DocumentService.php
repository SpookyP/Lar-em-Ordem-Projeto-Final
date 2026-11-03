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

    public function deleteDocument(\App\Models\Vault\Document $document): void
    {
        // Apagar o ficheiro físico e o seu registo
        if ($document->file_id) {
            $fileRecord = \App\Models\File\File::find($document->file_id);

            if ($fileRecord) {
                // Usa o disco correto configurado no teu projeto
                $disk = config('filesystems.cloud', 'r2');

                // Apaga o ficheiro físico
                if (\Illuminate\Support\Facades\Storage::disk($disk)->exists($fileRecord->path)) {
                    \Illuminate\Support\Facades\Storage::disk($disk)->delete($fileRecord->path);
                }

                // Apaga o registo do ficheiro na base de dados
                $fileRecord->delete();
            }
        } elseif ($document->ficheiro_path) {
            // Fallback: limpar ficheiros antigos guardados localmente antes da transição para R2
            if (\Illuminate\Support\Facades\Storage::disk('local')->exists($document->ficheiro_path)) {
                \Illuminate\Support\Facades\Storage::disk('local')->delete($document->ficheiro_path);
            }
        }

        // Apagar notificações do sino associadas a este documento
        \App\Models\Vault\Notification::where('property_id', $document->property_id)
            ->where('message', 'like', '%' . $document->name . '%')
            ->delete();

        // Apagar o próprio documento
        $document->delete();
    }
}
