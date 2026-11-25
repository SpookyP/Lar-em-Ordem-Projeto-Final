<?php

namespace App\Services\Condominium;

use App\Models\Condominium\Condominium;
use App\Models\Condominium\CondominiumDocument;
use App\Models\Condominium\CondominiumDocumentCategory;
use App\Services\FileStorage\FileStorageService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;

class CondominiumDocumentService
{

    public function __construct(private FileStorageService $fileStorage) {}

    /**
     * Condominium documents, most recent first, with an optional category filter.
     * With no condominium (a property that doesn't belong to one) it returns an empty page in the same format (links/meta).
     *
     * @param Condominium|null $condominium Condominium (null = property with no condominium).
     * @param int|null $categoryId Category (optional).
     * @param int $perPage Documents per page.
     * @return LengthAwarePaginator
     */
    public function list(?Condominium $condominium, ?int $categoryId = null, int $perPage = 5): LengthAwarePaginator
    {
        if (! $condominium) {
            return (new Paginator([], 0, $perPage, null, ['path' => Paginator::resolveCurrentPath()]))
                ->withQueryString();
        }

        return $condominium->documents()
            ->with(['category', 'file'])
            ->when($categoryId, fn ($q) => $q->where('condominium_document_category_id', $categoryId))
            ->orderByDesc('document_date')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Category catalog (for the filters and the admin form).
     *
     * @return Collection
     */
    public function categories(): Collection
    {
        return CondominiumDocumentCategory::orderBy('id')->get(['id', 'name']);
    }

    /**
     * Uploads the file to R2 (under the current admin) and creates the document.
     * If the database insert fails, the file is deleted from R2 again.
     *
     * @param Condominium $condominium Condominium.
     * @param array $data Validated data (includes 'file').
     * @return CondominiumDocument
     */
    public function create(Condominium $condominium, array $data): CondominiumDocument
    {
        /** @var UploadedFile $upload */
        $upload = $data['file'];

        $file = $this->fileStorage->uploadUserFile(
            $condominium->admin->user,
            $upload,
            'condominium_documents',
            $data['title']
        );

        try {
            $document = new CondominiumDocument(Arr::except($data, 'file'));
            $document->forceFill([
                'condominium_id' => $condominium->id,
                'file_id'        => $file->id,
            ])->save();
        } catch (\Throwable $e) {
            $this->fileStorage->deleteFile($file); // don't leave orphan files in R2
            throw $e;
        }

        return $document->load(['category', 'file']);
    }

    /**
     * Updates the document's details (the file stays the same).
     *
     * @param CondominiumDocument $document Document.
     * @param array $data Validated data.
     * @return CondominiumDocument
     */
    public function update(CondominiumDocument $document, array $data): CondominiumDocument
    {
        $document->update($data);

        return $document->load(['category', 'file']);
    }

    /**
     * Deletes the file from R2 and its row in files; the document goes with it by cascade (file_id).
     *
     * @param CondominiumDocument $document Document.
     * @return void
     */
    public function delete(CondominiumDocument $document): void
    {
        $this->fileStorage->deleteFile($document->file);
    }

    /**
     * The file itself, streamed through the API.
     */
    public function content(CondominiumDocument $document): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return $this->fileStorage->contentResponse($document->file);
    }
}