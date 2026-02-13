<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class MediaFile extends Model
{
    protected $fillable = [
        'disk',
        'path',
        'folder',
        'original_name',
        'filename',
        'mime_type',
        'size',
        'width',
        'height',
        'title',
        'alt_text',
        'caption',
        'created_by',
    ];

    protected $casts = [
        'size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk($this->disk ?? 'public')->url($this->path);
    }

    public function isImage(): bool
    {
        return is_string($this->mime_type) && str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Blade convenience: $media->is_image (bool)
     */
    public function getIsImageAttribute(): bool
    {
        return $this->isImage();
    }

    /**
     * Blade convenience: $media->extension (string)
     */
    public function getExtensionAttribute(): string
    {
        $path = (string) ($this->path ?? $this->filename ?? '');
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION) ?: '');
        return $ext;
    }

    /**
     * Blade convenience: $media->size_bytes (int)
     */
    public function getSizeBytesAttribute(): int
    {
        return (int) ($this->size ?? 0);
    }
}
