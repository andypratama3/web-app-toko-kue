<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class IncomingMediaHandler
{
    protected WhatsappMetaService $metaService;

    public function __construct(WhatsappMetaService $metaService)
    {
        $this->metaService = $metaService;
    }

    public function handlePaymentProof(string $mediaId): ?string
    {
        $path = $this->metaService->downloadMedia($mediaId);

        if (!$path) {
            return null;
        }

        return $this->compressImage($path);
    }

    public function handleReturnProof(string $mediaId): ?string
    {
        $path = $this->metaService->downloadMedia($mediaId);

        if (!$path) {
            return null;
        }

        return $this->compressImage($path, 60);
    }

    protected function compressImage(string $path, int $quality = 60): ?string
    {
        try {
            $fullPath = Storage::disk('public')->path($path);
            $image = \Intervention\Image\Laravel\Facades\Image::read($fullPath);

            $compressedPath = Str::before($path, '.') . '_compressed.jpg';
            $compressedFullPath = Storage::disk('public')->path($compressedPath);

            $image->encodeJpeg($quality)->save($compressedFullPath);

            Storage::disk('public')->delete($path);

            return $compressedPath;
        } catch (\Exception $e) {
            return $path;
        }
    }

    public function getMediaType(string $mimeType): string
    {
        return match(true) {
            str_starts_with($mimeType, 'image/') => 'image',
            str_starts_with($mimeType, 'video/') => 'video',
            str_starts_with($mimeType, 'audio/') => 'audio',
            str_starts_with($mimeType, 'application/') => 'document',
            default => 'file',
        };
    }
}
