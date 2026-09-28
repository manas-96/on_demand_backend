<?php

namespace App\Observers;

use App\Models\RequestPhoto;
use App\Services\Media\ImageUploadService;

class RequestPhotoObserver
{
    public function __construct(
        protected ImageUploadService $uploadService,
    ) {}

    /**
     * Handle the RequestPhoto "deleted" event.
     */
    public function deleted(RequestPhoto $requestPhoto): void
    {
        $this->uploadService->deleteImage($requestPhoto->file_path);
    }

    /**
     * Handle the RequestPhoto "force deleted" event.
     */
    public function forceDeleted(RequestPhoto $requestPhoto): void
    {
        $this->uploadService->deleteImage($requestPhoto->file_path);
    }
}
