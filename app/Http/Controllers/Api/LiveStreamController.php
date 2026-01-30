<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LiveStream;
use App\Models\AudioChunk;
use Illuminate\Http\Request;

class LiveStreamController extends Controller
{
    public function start(Request $request)
    {
        $stream = LiveStream::create([
            'user_id' => $request->user()->id,
            'started_at' => now(),
            'status' => 'active',
        ]);

        return response()->json([
            'message' => 'Live stream started',
            'live_stream_id' => $stream->id,
        ]);
    }

    public function uploadChunk(Request $request)
    {
        $request->validate([
            'live_stream_id' => 'required|exists:live_streams,id',
            'file' => 'required|file|mimes:mp3,wav,aac,m4a,3gp,ogg', // Added common audio formats
            'sequence_number' => 'required|integer',
            'duration' => 'nullable|numeric',
        ]);

        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('live_streams/' . $request->live_stream_id, 'public');

            $chunk = AudioChunk::create([
                'live_stream_id' => $request->live_stream_id,
                'file_path' => $path,
                'sequence_number' => $request->sequence_number,
                'duration' => $request->duration,
            ]);

            return response()->json([
                'message' => 'Chunk uploaded successfully',
                'chunk_id' => $chunk->id,
            ]);
        }

        return response()->json(['message' => 'No file uploaded'], 400);
    }

    public function end(Request $request)
    {
        $request->validate([
            'live_stream_id' => 'required|exists:live_streams,id'
        ]);

        $stream = LiveStream::where('id', $request->live_stream_id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $stream->update([
            'status' => 'ended',
            'ended_at' => now(),
        ]);

        return response()->json(['message' => 'Live stream ended']);
    }
}
