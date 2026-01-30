<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\AudioChunk;
use Illuminate\Http\Request;

class AdminAudioController extends Controller
{
    public function getChunks($streamId)
    {
        // Simple caching or just return all
        $chunks = AudioChunk::where('live_stream_id', $streamId)
            ->orderBy('sequence_number')
            ->get(['id', 'file_path', 'sequence_number', 'duration']);

        return response()->json($chunks);
    }
}
