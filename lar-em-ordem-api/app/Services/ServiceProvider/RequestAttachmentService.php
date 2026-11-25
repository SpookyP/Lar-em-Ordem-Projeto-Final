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
     * Updates a request's images: stores the new ones in R2 and removes the given ones.
     * The request can't end up with more than 2 images.
     * New images are stored before the old ones are deleted, so a failed upload
     * doesn't leave the request without the images it already had.
     *
     * @param AssistanceRequest $assistanceRequest Request the images belong to.
     * @param array<UploadedFile> $newImages Validated images to add.
     * @param array<string> $removeIds Ids of the images (RequestAttachment) to remove.
     * @return void
     */
    public function sync(AssistanceRequest $assistanceRequest, array $newImages = [], array $removeIds = []): void
    {
        // loads the images the resident asked to remove that belong to this request
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

        // deletes the file from R2 and its row in files; the attachment goes with it by cascade
        foreach ($toRemove as $attachment) {
            $this->fileStorage->deleteFile($attachment->file);
        }
    }
}