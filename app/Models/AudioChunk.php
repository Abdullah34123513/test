<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AudioChunk extends Model
{
    protected $fillable = ['live_stream_id', 'file_path', 'sequence_number', 'duration'];

    public function liveStream()
    {
        return $this->belongsTo(LiveStream::class);
    }
}
