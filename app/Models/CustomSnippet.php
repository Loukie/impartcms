<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class CustomSnippet extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'type',
        'name',
        'position',
        'is_enabled',
        'target_mode',
        'content',
    ];

    protected $casts = [
        'is_enabled' => 'bool',
        'deleted_at' => 'datetime',
    ];

    public function pages(): BelongsToMany
    {
        return $this->belongsToMany(Page::class, 'custom_snippet_pages');
    }

    public static function flushCache(): void
    {
        Cache::forget('custom_snippets:enabled:v1');
    }

    protected static function booted(): void
    {
        static::saved(fn () => static::flushCache());
        static::deleted(fn () => static::flushCache());
        static::restored(fn () => static::flushCache());
    }
}
