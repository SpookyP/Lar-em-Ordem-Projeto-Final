<?php

namespace App\Services\ServiceProvider;

use App\Models\ServiceProvider\AssistanceRequest;
use App\Models\ServiceProvider\RequestAttachment;
use App\Models\User\User;
use App\Services\FileStorage\FileStorageService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class RequestAttachmentService
{
    private const MAX_IMAGES = 2;

    public function __construct(private FileStorageService $fileStorage) {}

    /**
     * Guarda as imagens no R2 e associa-as ao pedido.
     * Só com o pedido aberto e até ao máximo de 2 imagens por pedido (contando as que já tem).
     *
     * @param AssistanceRequest $assistanceRequest Pedido a que se juntam as imagens.
     * @param User $user Utilizador que envia (dono dos ficheiros).
     * @param array<UploadedFile> $images Imagens validadas.
     * @return Collection Todas as imagens do pedido, com o ficheiro carregado.
     */
    public function addImages(AssistanceRequest $assistanceRequest, User $user, array $images): Collection
    {
        $this->ensureOpen($assistanceRequest);

        if ($assistanceRequest->attachments()->count() + count($images) > self::MAX_IMAGES) {
            throw ValidationException::withMessages([
                'images' => 'Cada pedido pode ter no máximo ' . self::MAX_IMAGES . ' imagens.',
            ]);
        }

        foreach ($images as $image) {
            $file = $this->fileStorage->uploadUserFile($user, $image, 'assistance_requests');

            $assistanceRequest->attachments()->create(['file_id' => $file->id]);
        }

        return $assistanceRequest->attachments()->with('file')->get();
    }
    

    /**
     * Remove uma imagem do pedido: apaga o ficheiro do R2 e o registo
     * (o anexo é apagado em cascata com o ficheiro). Só com o pedido aberto.
     *
     * @param AssistanceRequest $assistanceRequest Pedido a que a imagem pertence.
     * @param RequestAttachment $attachment Imagem a remover.
     * @return void
     */
    public function delete(AssistanceRequest $assistanceRequest, RequestAttachment $attachment): void
    {
        $this->ensureOpen($assistanceRequest);

        $this->fileStorage->deleteFile($attachment->file);
    }

    /**
     * Garante que o pedido está aberto; depois de aceite uma proposta as imagens já não mudam.
     *
     * @param AssistanceRequest $assistanceRequest Instância do pedido.
     * @return void
     */
    private function ensureOpen(AssistanceRequest $assistanceRequest): void
    {
        if (! $assistanceRequest->isOpen()) {
            throw ValidationException::withMessages([
                'status' => 'Só é possível alterar as imagens de pedidos abertos.',
            ]);
        }
    }
}