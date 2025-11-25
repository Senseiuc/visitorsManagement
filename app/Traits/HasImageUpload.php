<?php

namespace App\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

trait HasImageUpload
{
    /**
     * Detect if Cloudinary is properly configured
     */
    protected static function cloudinaryEnabled(): bool
    {
        return filled(config('cloudinary.cloud.cloud_name'))
            && filled(config('cloudinary.cloud.api_key'))
            && filled(config('cloudinary.cloud.api_secret'));
    }

    /**
     * Handle image upload to Cloudinary or Public disk
     * 
     * @param UploadedFile $file
     * @param string $folder
     * @return string The URL or path of the uploaded file
     */
    protected static function handleImageUpload(UploadedFile $file, string $folder = 'visitors'): string
    {
        if (! static::cloudinaryEnabled()) {
            return $file->store($folder, 'public');
        }

        // Upload to Cloudinary
        $upload = cloudinary()->upload(
            $file->getRealPath(),
            ['folder' => $folder]
        );

        return $upload->getSecurePath();
    }

    /**
     * Get the full URL for an image path (handles local vs remote)
     */
    protected static function getImageUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (str_starts_with($path, 'http')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }
}
