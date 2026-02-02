<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppVersion extends Model
{
    protected $fillable = [
        'version_code',
        'version_name',
        'apk_url',
        'release_notes',
        'is_force_update',
    ];
}
