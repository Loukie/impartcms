<?php

namespace App\Support;

use App\Models\MediaFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaImporter
{
    private string $disk = 'public';
    private ?int $userId = null;

    public function __construct(?int $userId = null)
    {
        $this->userId = $userId;
    }

    /**
     * Download an image or video from a URL and save it to the Media system.
     *
     * @param string $url External media URL
     * @param string|null $originalName Optional original filename
     * @param string|null $folder Optional folder (default: YYYY/MM)
     * @return MediaFile|null Returns MediaFile on success, null on failure
     */
    public function importFromUrl(string $url, ?string $originalName = null, ?string $folder = null): ?MediaFile
    {
        try {
            // Download the media file
            $response = Http::timeout(15)
                ->withoutVerifying()
                ->get($url);

            if (!$response->successful()) {
                \Log::info('MediaImporter: Failed to download', ['url' => $url, 'status' => $response->status()]);
                return null;
            }

            $fileData = $response->body();
            if (empty($fileData)) {
                \Log::info('MediaImporter: Empty response', ['url' => $url]);
                return null;
            }

            // Determine filename and extension
            if (!$originalName) {
                $originalName = basename(parse_url($url, PHP_URL_PATH) ?: 'media.jpg');
            }
            
            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION) ?: 'jpg');
            
            // Validate extension (images + videos)
            $allowedExts = [
                // Images
                'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico', 'avif', 'bmp', 'tiff', 'tif',
                // Videos
                'mp4', 'webm', 'ogg', 'ogv', 'mov', 'avi', 'wmv', 'flv', 'mkv', 'm4v', '3gp',
            ];
            if (!in_array($ext, $allowedExts)) {
                $ext = 'jpg';
            }

            // Generate unique filename
            $filename = Str::uuid() . '.' . $ext;
            $folder = $folder ?: now()->format('Y/m');
            $path = 'media/' . $folder . '/' . $filename;

            // Save to storage
            Storage::disk($this->disk)->put($path, $fileData);

            // Get mime type and size
            $fullPath = Storage::disk($this->disk)->path($path);
            $mime = mime_content_type($fullPath) ?: 'application/octet-stream';
            $size = filesize($fullPath) ?: 0;

            // Get dimensions for images only (not videos or SVG)
            $width = null;
            $height = null;
            $isImage = str_starts_with($mime, 'image/');
            $isVideo = str_starts_with($mime, 'video/');
            
            if ($isImage && $ext !== 'svg') {
                try {
                    $info = @getimagesize($fullPath);
                    if (is_array($info)) {
                        $width = (int) ($info[0] ?? 0) ?: null;
                        $height = (int) ($info[1] ?? 0) ?: null;
                    }
                } catch (\Throwable $e) {
                    // Ignore
                }
            }

            // Create MediaFile record
            $media = MediaFile::create([
                'disk' => $this->disk,
                'path' => $path,
                'folder' => $folder,
                'original_name' => $originalName,
                'filename' => $filename,
                'mime_type' => $mime,
                'size' => $size,
                'width' => $width,
                'height' => $height,
                'title' => pathinfo($originalName, PATHINFO_FILENAME),
                'alt_text' => null,
                'caption' => 'Imported from ' . parse_url($url, PHP_URL_HOST),
                'created_by' => $this->userId,
            ]);

            \Log::info('MediaImporter: Media imported successfully', [
                'url' => $url,
                'media_id' => $media->id,
                'path' => $path,
                'type' => $isVideo ? 'video' : ($isImage ? 'image' : 'file'),
            ]);

            return $media;
        } catch (\Throwable $e) {
            \Log::error('MediaImporter: Failed to import media', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Import multiple images/videos from URLs.
     *
     * @param array $urls Array of media URLs
     * @param string|null $folder Optional folder
     * @return array Array of MediaFile objects (some may be null if import failed)
     */
    public function importMultiple(array $urls, ?string $folder = null): array
    {
        $results = [];
        foreach ($urls as $url) {
            $results[$url] = $this->importFromUrl($url, null, $folder);
        }
        return $results;
    }
}
