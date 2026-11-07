<?php

namespace App\Services\FileStorage;

use App\Models\File\File;
use App\Models\User\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

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
     * Delete file from R2 and the database.
     * Inside a transaction the object is removed only after the commit, so a rollback
     * never leaves a row pointing at a file that is already gone.
     */
    public function deleteFile(File $file): bool
    {
        $path = $file->path;
        $deleted = $file->delete();

        DB::afterCommit(fn () => Storage::disk($this->disk)->delete($path));

        return $deleted;
    }
}