<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormRecipientRule extends Model
{
    protected $fillable = [
        'form_id',
        'page_id',
        'user_id',
        'recipients',
        'from_name',
        'from_email',
        'reply_to_email',
    ];

    protected $casts = [
        'recipients' => 'array',
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
