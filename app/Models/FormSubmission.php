<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormSubmission extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'form_id',
        'page_id',
        'user_id',
        'payload',
        'ip',
        'user_agent',
        'to_email',
        'mail_status',
        'mail_sent_at',
        'mail_error',
        'spam_reason',
        'created_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'created_at' => 'datetime',
        'mail_sent_at' => 'datetime',
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }
}
