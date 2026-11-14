<?php

namespace App\Services\FileStorage;

use App\Models\File\File;
use App\Models\User\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileStorageService
{
    private string $disk = 'r2';
    
    // types the browser may show inside the app; anything else is only ever downloaded,
    // so an uploaded HTML or SVG file can never run in our own origin
    private const INLINE_TYPES = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];

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
     * Streams the file itself through the API (no public or signed link).
     * The caller must have authorized the user before calling this.
     */
    public function contentResponse(File $file): StreamedResponse
    {
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk($this->disk);

        abort_unless($disk->exists($file->path), 404, 'O ficheiro já não existe.');

        $inline = in_array($file->mime_type, self::INLINE_TYPES, true);

        return $disk->response(
            $file->path,
            $file->original_name,
            [
                'Content-Type'           => $inline ? $file->mime_type : 'application/octet-stream',
                'Cache-Control'          => 'private, no-store',
                'X-Content-Type-Options' => 'nosniff',
            ],
            $inline ? 'inline' : 'attachment',
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