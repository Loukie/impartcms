<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Page extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'body',
        'status',
        'template',
        'published_at',
        'is_homepage',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'is_homepage'  => 'bool',
        'deleted_at'   => 'datetime',
    ];

    public function seo(): HasOne
    {
        return $this->hasOne(SeoMeta::class);
    }

    protected static function booted(): void
    {
        static::deleting(function (self $page) {
            // If permanently deleting, also delete SEO row
            if (method_exists($page, 'isForceDeleting') && $page->isForceDeleting()) {
                $page->seo()->delete();
            }
        });
    }
}
