<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommandLog extends Model
{
    protected $fillable = [
        'user_id',
        'command',
        'status',
        'payload',
        'response_message',
        'delivered_at',
        'executed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'delivered_at' => 'datetime',
        'executed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
