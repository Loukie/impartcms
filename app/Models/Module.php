<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'provider_class',
        'version',
        'enabled',
        'settings',
    ];

    protected $casts = [
        'enabled' => 'bool',
        'settings' => 'array',
    ];
}
