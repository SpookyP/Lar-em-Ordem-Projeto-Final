<?php

namespace App\Services\FileStorage;

use App\Models\File\File;
use App\Models\User\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileStorageService
{
    private string $disk = 'r2';

    public function uploadUserFile(User $user, UploadedFile $uploadedFile, string $category, ?string $customTitle = null): File
    {
        $fileId = (string) Str::ulid();
        $extension = $uploadedFile->getClientOriginalExtension();
        $filename = "{$fileId}.{$extension}";

        $path = "users/{$user->id}/{$category}/{$filename}";

        Storage::disk($this->disk)->putFileAs(
            "users/{$user->id}/{$category}",
            $uploadedFile,
            $filename
        );

        return File::create([
            'user_id'       => $user->id,
            'title'         => $customTitle ?? pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME),
            'original_name' => $uploadedFile->getClientOriginalName(),
            'path'          => $path,
            'mime_type'     => $uploadedFile->getClientMimeType(),
            'size_in_bytes' => $uploadedFile->getSize(),
            'category'      => $category,
        ]);
    }

    /**
     * Gera um URL temporário e assinado para visualização/download do ficheiro.
     */
    public function getSecureUrl(File $file, int $expirationMinutes = 30): string
    {
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk($this->disk);

        return $disk->temporaryUrl(
            $file->path,
            now()->addMinutes($expirationMinutes)
        );
    }

    /**
     * Elimina o ficheiro do R2 e o registo da BD.
     */
    public function deleteFile(File $file): bool
    {
        Storage::disk($this->disk)->delete($file->path);
        return $file->delete();
    }
}