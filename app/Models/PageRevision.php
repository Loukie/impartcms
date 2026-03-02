<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageRevision extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'page_id',
        'body',
        'created_by',
        'reason',
        'meta',
    ];

    protected $casts = [
        'page_id' => 'int',
        'created_by' => 'int',
        'meta' => 'array',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}
