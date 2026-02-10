<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Form extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'fields',
        'settings',
        'is_active',
    ];

    protected $casts = [
        'fields' => 'array',
        'settings' => 'array',
        'is_active' => 'bool',
    ];

    public function recipientRules(): HasMany
    {
        return $this->hasMany(FormRecipientRule::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class);
    }
}
