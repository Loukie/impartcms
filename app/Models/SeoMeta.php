<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeoMeta extends Model
{
    protected $table = 'seo_meta';

    protected $fillable = [
        'page_id',
        'meta_title',
        'meta_description',
        'canonical_url',
        'robots',
        'og_title',
        'og_description',
        'og_image_url',
        'twitter_title',
        'twitter_description',
        'twitter_image_url',
        'extras',
    ];

    protected $casts = [
        'extras' => 'array',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}
