<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\ImageManager;

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
            $compressedPath = Str::before($path, '.') . '_compressed.jpg';

            // Intervention Image v3 (installed: 3.11.6): ImageManager + JpegEncoder
            // V3 removed Laravel facade and encodeJpeg(); use ImageManager::read() + encode(new JpegEncoder($q))
            // If ImageManager fails, fall back to the original uncompressed file.
            $manager = ImageManager::gd();
            $image = $manager->read($fullPath);
            $image->encode(new JpegEncoder($quality))->save(Storage::disk('public')->path($compressedPath));

            Storage::disk('public')->delete($path);
            return $compressedPath;
        } catch (\Throwable $e) {
            // C2 FIX: catch \Throwable (not \Exception) because Image errors are Error, not Exception.
            // Log the failure but DO NOT kill the webhook job — return the original file so the order can still proceed.
            \Illuminate\Support\Facades\Log::channel('whatsapp')->warning('Image compression skipped (intervention v3)', [
                'path' => $path,
                'message' => $e->getMessage(),
                'class' => get_class($e),
            ]);
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
