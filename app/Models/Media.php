<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    protected $fillable = ['user_id', 'category', 'file_path', 'file_type', 'file_mime', 'file_size'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
