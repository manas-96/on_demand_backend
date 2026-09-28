<?php

namespace App\Services\Media;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageUploadService
{
    /**
     * Upload a single image file to public storage.
     */
    public function uploadImage(UploadedFile $file, string $folder = 'requests', string $disk = 'public'): string
    {
        return $file->store($folder, $disk);
    }

    /**
     * Upload multiple image files to public storage.
     *
     * @param  array<int, UploadedFile>  $files
     * @return array<int, string>
     */
    public function uploadMultiple(array $files, string $folder = 'requests', string $disk = 'public'): array
    {
        $paths = [];
        foreach ($files as $file) {
            if ($file instanceof UploadedFile && $file->isValid()) {
                $paths[] = $this->uploadImage($file, $folder, $disk);
            }
        }

        return $paths;
    }

    /**
     * Delete an image file from storage by relative path or URL.
     */
    public function deleteImage(?string $filePath, string $disk = 'public'): bool
    {
        if (empty($filePath)) {
            return false;
        }

        if (Str::startsWith($filePath, ['http://', 'https://'])) {
            return false;
        }

        $cleanPath = ltrim($filePath, '/');
        if (Str::startsWith($cleanPath, 'storage/')) {
            $cleanPath = Str::after($cleanPath, 'storage/');
        } elseif (Str::startsWith($cleanPath, 'public/')) {
            $cleanPath = Str::after($cleanPath, 'public/');
        }

        if (Storage::disk($disk)->exists($cleanPath)) {
            return Storage::disk($disk)->delete($cleanPath);
        }

        return false;
    }

    /**
     * Delete multiple image files from storage.
     *
     * @param  array<int, string>  $filePaths
     */
    public function deleteMultiple(array $filePaths, string $disk = 'public'): void
    {
        foreach ($filePaths as $filePath) {
            $this->deleteImage($filePath, $disk);
        }
    }
}
