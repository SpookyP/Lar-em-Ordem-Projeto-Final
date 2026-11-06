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
                // same disk and rules as every other upload (object in R2 + row in `files`)
                $this->fileStorageService->deleteFile($fileRecord);
            }
        } elseif ($document->ficheiro_path) {
            // Fallback: limpar ficheiros antigos guardados localmente antes da transição para R2
            if (\Illuminate\Support\Facades\Storage::disk('local')->exists($document->ficheiro_path)) {
                \Illuminate\Support\Facades\Storage::disk('local')->delete($document->ficheiro_path);
            }
        }

        // Apagar notificações pelo document_id
        \App\Models\Vault\Notification::where('document_id', $document->id)->delete();

        // Apagar o próprio documento
        $document->delete();
    }

    public function updateDocument(\App\Models\Vault\Document $document, array $validatedData, ?\Illuminate\Http\UploadedFile $file = null): \App\Models\Vault\Document
    {
        $extractedData = $document->extracted_data ?? [];
        $fileRecordId = $document->file_id;
        $fileHash = $document->file_hash;

        $documentName = $validatedData['name'] ?? $document->name;

        // Se o utilizador enviou um ficheiro novo para substituir o antigo
        if ($file) {
            // Extrai a info do novo PDF (se existir)
            $newExtractedData = $this->pdfExtractionService->extractInfo($file->getRealPath());
            $extractedData = !empty($newExtractedData) ? $newExtractedData : $extractedData;

            // O novo Hash já foi validado no FormRequest (não é duplicado noutro doc)
            $fileHash = hash_file('sha256', $file->getRealPath());

            // Guarda o novo ficheiro
            $user = auth()->user();
            $newFileRecord = $this->fileStorageService->uploadUserFile(
                $user, $file, 'vault_documents', $documentName
            );

            // Apaga o ficheiro antigo
            if ($document->file_id) {
                $oldFileRecord = \App\Models\File\File::find($document->file_id);
                if ($oldFileRecord) {
                    $this->fileStorageService->deleteFile($oldFileRecord);
                }
            }

            $fileRecordId = $newFileRecord->id;
        }

        // Lógica de Datas (Se enviou manuais, usa essas. Senão, tenta as do PDF extraído. Senão, mantém as antigas)
        $issueDate = $validatedData['issue_date'] ?? $extractedData['issue_date'] ?? $document->issue_date;
        $expirationDate = $validatedData['expiration_date'] ?? $extractedData['expiration_date'] ?? $document->expiration_date;

        // Limpa todas as notificações associadas ao documento antigo pelo ID
        \App\Models\Vault\Notification::where('document_id', $document->id)->delete();

        // Atualiza o documento na Base de Dados
        $document->update([
            'document_category_id' => $validatedData['document_category_id'] ?? $document->document_category_id,
            'name' => $documentName,
            'description' => $validatedData['description'] ?? $document->description,
            'file_id' => $fileRecordId,
            'file_hash' => $fileHash,
            'issue_date' => $issueDate,
            'expiration_date' => $expirationDate,
            'extracted_data' => $extractedData,
        ]);

        // Se a nova data de validade estiver próxima, regenera a notificação
        $this->documentExpiryService->checkSingleDocument($document);

        return $document;
    }
}
