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
    /** Minutos de validade do link de download. */
    private const DOWNLOAD_MINUTES = 30;

    public function __construct(private FileStorageService $fileStorage) {}

    /**
     * Documentos do condomínio, mais recentes primeiro, com filtro opcional por categoria.
     * Sem condomínio (casa que não pertence a nenhum): página vazia, com o mesmo formato (links/meta).
     *
     * @param Condominium|null $condominium Condomínio (null = casa sem condomínio).
     * @param int|null $categoryId Categoria (opcional).
     * @param int $perPage Número de documentos por página.
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
     * Catálogo de categorias (para os filtros e para o formulário do administrador).
     *
     * @return Collection
     */
    public function categories(): Collection
    {
        return CondominiumDocumentCategory::orderBy('id')->get(['id', 'name']);
    }

    /**
     * Carrega o ficheiro no R2 (em nome do administrador atual) e cria o documento.
     * Se a gravação na base de dados falhar, o ficheiro é apagado do R2.
     *
     * @param Condominium $condominium Condomínio.
     * @param array $data Dados validados (inclui 'file').
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
            $this->fileStorage->deleteFile($file); // não deixar ficheiros órfãos no R2
            throw $e;
        }

        return $document->load(['category', 'file']);
    }

    /**
     * Atualiza os dados do documento (o ficheiro não muda).
     *
     * @param CondominiumDocument $document Documento.
     * @param array $data Dados validados.
     * @return CondominiumDocument
     */
    public function update(CondominiumDocument $document, array $data): CondominiumDocument
    {
        $document->update($data);

        return $document->load(['category', 'file']);
    }

    /**
     * Apaga o ficheiro do R2 e o registo em files; o documento sai em cascata (file_id).
     *
     * @param CondominiumDocument $document Documento.
     * @return void
     */
    public function delete(CondominiumDocument $document): void
    {
        $this->fileStorage->deleteFile($document->file);
    }

    /**
     * Link temporário (assinado) para ver/descarregar o ficheiro.
     *
     * @param CondominiumDocument $document Documento.
     * @return array{url: string, expires_at: \Illuminate\Support\Carbon}
     */
    public function downloadLink(CondominiumDocument $document): array
    {
        return [
            'url'        => $this->fileStorage->getSecureUrl($document->file, self::DOWNLOAD_MINUTES),
            'expires_at' => now()->addMinutes(self::DOWNLOAD_MINUTES),
        ];
    }
}