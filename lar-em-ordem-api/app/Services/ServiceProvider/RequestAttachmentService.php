<?php

namespace App\Services\ServiceProvider;

use App\Models\ServiceProvider\AssistanceRequest;
use App\Services\FileStorage\FileStorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class RequestAttachmentService
{
    private const MAX_IMAGES = 2;

    public function __construct(private FileStorageService $fileStorage) {}

    /**
     * Atualiza as imagens de um pedido: guarda as novas no R2 e remove as indicadas.
     * No fim o pedido não pode ter mais de 2 imagens.
     * As novas são guardadas antes de apagar as antigas, para que uma falha no upload
     * não deixe o pedido sem as imagens que já tinha.
     *
     * @param AssistanceRequest $assistanceRequest Pedido a que pertencem as imagens.
     * @param array<UploadedFile> $newImages Imagens validadas a juntar.
     * @param array<string> $removeIds IDs das imagens (RequestAttachment) a remover.
     * @return void
     */
    public function sync(AssistanceRequest $assistanceRequest, array $newImages = [], array $removeIds = []): void
    {
        //vai buscar à base de dados as imagens que o morador pediu para remover, que pertencem a este pedido
        $toRemove = $assistanceRequest->attachments()->with('file')->whereIn('id', $removeIds)->get();

        if ($toRemove->count() !== count($removeIds)) {
            throw ValidationException::withMessages([
                'remove_attachment_ids' => 'Uma ou mais imagens indicadas não pertencem a este pedido.',
            ]);
        }

        $total = $assistanceRequest->attachments()->count() - $toRemove->count() + count($newImages);

        if ($total > self::MAX_IMAGES) {
            throw ValidationException::withMessages([
                'images' => 'Cada pedido pode ter no máximo ' . self::MAX_IMAGES . ' imagens.',
            ]);
        }

        foreach ($newImages as $image) {
            $file = $this->fileStorage->uploadUserFile($assistanceRequest->resident->user, $image, 'assistance_requests');

            $assistanceRequest->attachments()->create(['file_id' => $file->id]);
        }

        // apaga o ficheiro do R2 e o registo em files; o anexo é apagado em cascata
        foreach ($toRemove as $attachment) {
            $this->fileStorage->deleteFile($attachment->file);
        }
    }
}