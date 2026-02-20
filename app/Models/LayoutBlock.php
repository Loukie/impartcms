<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class LayoutBlock extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'type',          // header | footer
        'name',
        'is_enabled',
        'target_mode',   // global | only | except
        'priority',      // lower = wins
        'content',       // HTML (supports shortcodes)
    ];

    protected $casts = [
        'is_enabled' => 'bool',
        'priority' => 'int',
        'deleted_at' => 'datetime',
    ];

    public function pages(): BelongsToMany
    {
        return $this->belongsToMany(Page::class, 'layout_block_pages');
    }

    public static function flushCache(): void
    {
        Cache::forget('layout_blocks:enabled:v1');
    }

    protected static function booted(): void
    {
        static::saved(fn () => static::flushCache());
        static::deleted(fn () => static::flushCache());
        static::restored(fn () => static::flushCache());
    }
}
